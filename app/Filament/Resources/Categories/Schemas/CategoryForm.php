<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use App\Models\Country;

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
                FileUpload::make('image')
                    ->image()
                    ->disk('public')
                    ->directory('categories')
                    ->visibility('public')
                    ->columnSpanFull(),
            ]);
    }
}
