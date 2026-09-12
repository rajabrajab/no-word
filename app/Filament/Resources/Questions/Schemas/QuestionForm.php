<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Category;
use App\Models\Country;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('country_id')
                ->label(__('panel.country'))
                ->options(Country::all()->pluck('name', 'id'))
                ->required()
                ->reactive()
                ->afterStateUpdated(function (callable $set) {
                    $set('category_id', null);
                })
                ->columnSpanFull(),

            Select::make('category_id')
                ->label(__('panel.category'))
                ->options(function (callable $get) {
                    $countryId = $get('country_id');
                    $currentCategoryId = $get('category_id');

                    if (! $countryId) {
                        // If no country selected but there's a current category, show it
                        if ($currentCategoryId) {
                            return Category::where('id', $currentCategoryId)->pluck('name', 'id');
                        }

                        return [];
                    }

                    $query = Category::where('country_id', $countryId);

                    // When editing, include the current category even if it doesn't match the country
                    // (handles data inconsistency cases)
                    if ($currentCategoryId) {
                        $query->orWhere('id', $currentCategoryId);
                    }

                    return $query->pluck('name', 'id');
                })
                ->required()
                ->reactive()
                ->columnSpanFull(),

            TextInput::make('question')
                ->label(__('panel.question'))
                ->required(),

            TextInput::make('answer')
                ->label(__('panel.answer'))
                ->required(),

            TextInput::make('hint')
                ->label(__('panel.hint'))
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
                ->acceptedFileTypes(['image/*', 'video/*', 'audio/*'])
                ->multiple(false)
                ->maxFiles(1)
                ->disk('public')
                ->directory('questions')
                ->visibility('public')
                ->columnSpanFull()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('media_type', self::resolveMediaType($state));
                }),

            Hidden::make('media_type')
                ->default(''),

            FileUpload::make('answer_media')
                ->label(__('panel.answer_media'))
                ->acceptedFileTypes(['image/*', 'video/*', 'audio/*'])
                ->multiple(false)
                ->maxFiles(1)
                ->disk('public')
                ->directory('answers')
                ->visibility('public')
                ->columnSpanFull()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('answer_media_type', self::resolveMediaType($state));
                }),

            Hidden::make('answer_media_type')
                ->default(''),

            Checkbox::make('generate_qr_code')
                ->label(__('panel.generate_qr_code') ?: 'Generate QR Code')
                ->default(false)
                ->columnSpanFull(),
        ]);
    }

    /**
     * Map an uploaded file to the coarse media type stored alongside it.
     */
    public static function resolveMediaType(mixed $state): ?string
    {
        if (! $state) {
            return null;
        }

        $mime = null;

        if ($state instanceof TemporaryUploadedFile) {
            $mime = $state->getMimeType();
        } elseif (is_string($state)) {
            $fullPath = Storage::disk('public')->path($state);
            $mime = File::exists($fullPath) ? File::mimeType($fullPath) : null;
        }

        if (! is_string($mime)) {
            return null;
        }

        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            default => null,
        };
    }
}
