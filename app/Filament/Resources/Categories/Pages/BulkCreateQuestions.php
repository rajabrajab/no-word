<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use App\Models\Question;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BulkCreateQuestions extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = CategoryResource::class;

    protected string $view = 'filament.pages.bulk-create-questions';

    public function getTitle(): string
    {
        return __('panel.bulk_create_questions_title', ['category' => $this->record->name]);
    }

    public ?Category $record = null;

    public array $data = [];

    public function mount(Category $record): void
    {
        $this->record = $record;
        $this->form->fill([
            'questions' => [
                ['question' => '', 'answer' => '', 'media' => null],
            ],
        ]);
    }

    public function form($form)
    {
        return $form
            ->schema([
                Repeater::make('questions')
                    ->label(__('panel.questions'))
                    ->minItems(1)
                    ->columns(1)
                    ->schema([
                            Textarea::make('question')
                                ->label(__('panel.question'))
                                ->rows(3)
                                ->required(),

                            TextInput::make('answer')
                                ->label(__('panel.answer'))
                                ->required(),

                            FileUpload::make('media')
                                ->label(__('panel.media'))
                                ->disk('public')
                                ->directory('questions')
                                ->visibility('public')
                                ->maxFiles(1)
                                ->columnSpanFull()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (!$state) {
                                        $set('media_type', null);
                                        return;
                                    }

                                    $mime = null;

                                    if ($state instanceof TemporaryUploadedFile) {
                                        $mime = $state->getMimeType();
                                    } elseif (is_string($state)) {
                                        $fullPath = Storage::disk('public')->path($state);
                                        $mime = File::exists($fullPath) ? File::mimeType($fullPath) : null;
                                    }

                                    if (is_string($mime)) {
                                        if (str_starts_with($mime, 'image/')) {
                                            $set('media_type', 'image');
                                            return;
                                        }
                                        if (str_starts_with($mime, 'video/')) {
                                            $set('media_type', 'video');
                                            return;
                                        }
                                    }

                                    $set('media_type', null);
                                }),

                            Hidden::make('media_type')->default(''),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('panel.save'))
                ->action('save')
                ,
            Action::make('cancel')
                ->label(__('panel.cancel'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $questions = $state['questions'] ?? [];

        if (empty($questions)) {
            Notification::make()
                ->title(__('panel.no_questions_to_save') ?: 'No questions to save')
                ->warning()
                ->send();
            return;
        }

        DB::transaction(function () use ($questions) {
            foreach ($questions as $item) {
                if (!trim((string)($item['question'] ?? '')) || !trim((string)($item['answer'] ?? ''))) {
                    continue;
                }

                $mediaPath = $item['media'] ?? null;

                \App\Models\Question::create([
                    'category_id' => $this->record->id,
                    'question' => $item['question'],
                    'answer' => $item['answer'],
                    'media' => $mediaPath,
                    'media_type' => $item['media_type'] ?? null,
                ]);
            }
        });

        Notification::make()
            ->title(__('panel.saved_successfully') ?: 'Saved successfully')
            ->success()
            ->send();

        $this->redirect(\App\Filament\Resources\Categories\CategoryResource::getUrl('index'));

    }
}


