<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use App\Models\Country;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                Select::make('country_id')->options(Country::all()->pluck('name', 'id'))->required()->label(__('panel.country')),
                FileUpload::make('image')->image()
                ->disk('public')
                ->directory('categories')
                ->visibility('public')
                ->columnSpanFull(),
            ]);
    }
}
