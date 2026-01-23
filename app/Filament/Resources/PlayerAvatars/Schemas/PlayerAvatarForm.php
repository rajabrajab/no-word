<?php

namespace App\Filament\Resources\PlayerAvatars\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;

class PlayerAvatarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('avatar_path')
                ->label(__('panel.avatar'))
                ->image()
                ->required()
                ->disk('public')
                ->directory('player_avatars')
                ->visibility('public')
                ->columnSpanFull(),
        ]);
    }
}

