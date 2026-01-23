<?php

namespace App\Filament\Resources\PlayerAvatars\Pages;

use App\Filament\Resources\PlayerAvatars\PlayerAvatarResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlayerAvatar extends CreateRecord
{
    protected static string $resource = PlayerAvatarResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

