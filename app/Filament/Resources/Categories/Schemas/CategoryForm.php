<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Models\Country;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('panel.name'))
                    ->required(),
                Textarea::make('description')
                    ->label(__('panel.description'))
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('country_id')
                    ->options(Country::all()->pluck('name', 'id'))
                    ->required()
                    ->label(__('panel.country')),
                Select::make('language')
                    ->label(__('panel.language'))
                    ->helperText(__('panel.language_hint'))
                    ->options([
                        Category::LANGUAGE_AR => __('panel.language_ar'),
                        Category::LANGUAGE_EN => __('panel.language_en'),
                        Category::LANGUAGE_BOTH => __('panel.language_both'),
                    ])
                    ->default(Category::LANGUAGE_BOTH)
                    ->required(),
                TextInput::make('answer_time_200')
                    ->label(__('panel.answer_time_200'))
                    ->suffix(__('panel.seconds'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(600)
                    ->default(90)
                    ->required(),
                TextInput::make('answer_time_400')
                    ->label(__('panel.answer_time_400'))
                    ->suffix(__('panel.seconds'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(600)
                    ->default(60)
                    ->required(),
                TextInput::make('answer_time_600')
                    ->label(__('panel.answer_time_600'))
                    ->suffix(__('panel.seconds'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(600)
                    ->default(30)
                    ->required(),
                FileUpload::make('image')
                    ->image()
                    ->disk('public')
                    ->directory('categories')
                    ->visibility('public')
                    ->columnSpanFull(),
            ]);
    }
}
