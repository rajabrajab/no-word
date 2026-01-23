<?php

namespace App\Filament\Resources\HelpingMethods\Pages;

use App\Filament\Resources\HelpingMethods\HelpingMethodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHelpingMethod extends EditRecord
{
    protected static string $resource = HelpingMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

