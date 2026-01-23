<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\Hidden;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category_id')
                ->label(__('panel.category'))
                ->options(Category::pluck('name', 'id'))
                ->required()
                ->columnSpanFull(),

            TextInput::make('question')
                ->label(__('panel.question'))
                ->required(),

            TextInput::make('answer')
                ->label(__('panel.answer'))
                ->required(),

            Select::make('score')
                ->label(__('panel.score'))
                ->options([
                    200 => '200',
                    400 => '400',
                    600 => '600',
                ])
                ->required()
                ->columnSpanFull()
                ->native(false),

            FileUpload::make('media')
                ->label(__('panel.media'))
                ->acceptedFileTypes(['image/*', 'video/*','pdf'])
                ->multiple(false)
                ->maxFiles(1)
                ->disk('public')
                ->directory('questions')
                ->visibility('public')
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
                        $mime = Storage::disk('public')->mimeType($state) ?: null;
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

            Hidden::make('media_type')
                ->default(''),
        ]);
    }
}
