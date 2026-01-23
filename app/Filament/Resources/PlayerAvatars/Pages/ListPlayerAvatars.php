<?php

namespace App\Filament\Resources\PlayerAvatars\Pages;

use App\Filament\Resources\PlayerAvatars\PlayerAvatarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlayerAvatars extends ListRecords
{
    protected static string $resource = PlayerAvatarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

