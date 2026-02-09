<?php

namespace App\Filament\Resources\Packages\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

class PackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label(__('panel.name'))->required(),
                TextInput::make('price')->label(__('panel.price'))->required(),
                TextInput::make('games_count')->label(__('panel.games_count'))->required(),
                FileUpload::make('image')
                    ->label(__('panel.image'))
                    ->image()
                    ->disk('public')
                    ->directory('packages')
                    ->visibility('public')
                    ->columnSpanFull(),
            ]);
    }
}
