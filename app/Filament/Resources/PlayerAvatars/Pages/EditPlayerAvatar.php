<?php

namespace App\Filament\Resources\PlayerAvatars\Pages;

use App\Filament\Resources\PlayerAvatars\PlayerAvatarResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlayerAvatar extends EditRecord
{
    protected static string $resource = PlayerAvatarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

