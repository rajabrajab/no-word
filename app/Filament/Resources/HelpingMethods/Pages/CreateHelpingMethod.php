<?php

namespace App\Filament\Resources\HelpingMethods\Pages;

use App\Filament\Resources\HelpingMethods\HelpingMethodResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHelpingMethod extends CreateRecord
{
    protected static string $resource = HelpingMethodResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

