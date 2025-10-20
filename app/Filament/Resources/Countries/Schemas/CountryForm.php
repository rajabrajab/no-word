<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label(__('panel.name'))->columnSpanFull(),
                FileUpload::make('image')
                ->label(__('panel.image'))
                ->image()
                ->disk('public')
                ->directory('countries')
                ->visibility('public')
                ->columnSpanFull(),
        ]);
    }
}
