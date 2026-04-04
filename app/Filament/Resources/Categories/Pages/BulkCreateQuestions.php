<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Exports\CategoryBulkQuestionsTemplateExport;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use App\Services\CategoryBulkQuestionsExcelImporter;
use App\Services\QrCodeService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;

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
                ['question' => '', 'answer' => '', 'hint' => '', 'score' => null, 'media' => null],
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

                            TextInput::make('hint')
                                ->label(__('panel.hint')),

                            TextInput::make('score')
                                ->label(__('panel.score'))
                                ->numeric()
                                ->minValue(0),

                            FileUpload::make('media')
                                ->label(__('panel.media'))
                                ->acceptedFileTypes(['image/*', 'video/*', 'audio/*'])
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
                                        if (str_starts_with($mime, 'audio/')) {
                                            $set('media_type', 'audio');
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

    /**
     * Register Excel actions for the collapsible section (not the page header).
     */
    public function cacheInteractsWithHeaderActions(): void
    {
        $this->cachedHeaderActions = [];

        foreach ($this->getBulkExcelActions() as $action) {
            $this->cacheAction($action);
        }
    }

    /**
     * @return list<Action>
     */
    protected function getBulkExcelActions(): array
    {
        return [
            Action::make('downloadCategoryTemplate')
                ->label(__('panel.bulk_excel_download_template'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $name = 'category-'.$this->record->id.'-questions-template.xlsx';

                    return Excel::download(new CategoryBulkQuestionsTemplateExport, $name);
                }),
            Action::make('importCategoryExcel')
                ->label(__('panel.bulk_excel_import'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label(__('panel.excel_file'))
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ]),
                ])
                ->action(function (array $data): void {
                    $path = $data['file'] ?? null;
                    if (! $path) {
                        Notification::make()
                            ->title(__('panel.bulk_excel_no_file'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $importer = new CategoryBulkQuestionsExcelImporter(
                        $this->record->id,
                        app(QrCodeService::class),
                    );
                    $absolutePath = Storage::disk('local')->path(
                        is_array($path) ? ($path[0] ?? '') : $path
                    );
                    if (! is_file($absolutePath)) {
                        Notification::make()
                            ->title(__('panel.bulk_excel_file_missing'))
                            ->danger()
                            ->send();

                        return;
                    }
                    $importer->import($absolutePath);

                    if (! empty($importer->errors)) {
                        $body = collect($importer->errors)
                            ->take(5)
                            ->map(fn ($e) => __('panel.bulk_excel_row').' '.$e['row'].': '.implode(' | ', $e['errors']))
                            ->implode("\n");

                        $notification = Notification::make()->body($body);

                        if ($importer->created > 0) {
                            $notification
                                ->title(__('panel.bulk_excel_import_partial', [
                                    'created' => $importer->created,
                                    'skipped' => $importer->skipped,
                                ]))
                                ->warning();
                        } else {
                            $notification
                                ->title(__('panel.bulk_excel_import_failed'))
                                ->danger();
                        }

                        $notification->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('panel.bulk_excel_import_done', ['count' => $importer->created]))
                        ->success()
                        ->send();

                    $this->redirect(CategoryResource::getUrl('index'));
                }),
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
            $qrCodeService = app(QrCodeService::class);

            foreach ($questions as $item) {
                if (! trim((string) ($item['question'] ?? '')) || ! trim((string) ($item['answer'] ?? ''))) {
                    continue;
                }

                $mediaPath = $item['media'] ?? null;
                $score = $item['score'] ?? null;
                $score = $score === '' || $score === null ? null : (int) $score;

                $question = \App\Models\Question::create([
                    'category_id' => $this->record->id,
                    'question' => $item['question'],
                    'answer' => $item['answer'],
                    'hint' => filled($item['hint'] ?? null) ? $item['hint'] : null,
                    'score' => $score,
                    'media' => $mediaPath,
                    'media_type' => $item['media_type'] ?? null,
                ]);

                $question->update([
                    'qr_code' => $qrCodeService->generateForQuestion($question),
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


