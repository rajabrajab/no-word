<?php

namespace App\Filament\Resources\HelpingMethods\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;

class HelpingMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('panel.name'))
                ->required()
                ->columnSpanFull(),

            Textarea::make('description')
                ->label(__('panel.description'))
                ->rows(4)
                ->columnSpanFull(),

            FileUpload::make('icon')
                ->label(__('panel.icon'))
                ->image()
                ->disk('public')
                ->directory('helping_methods')
                ->visibility('public')
                ->columnSpanFull(),
        ]);
    }
}

