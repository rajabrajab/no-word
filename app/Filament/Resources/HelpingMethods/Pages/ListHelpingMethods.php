<?php

namespace App\Filament\Resources\HelpingMethods\Pages;

use App\Filament\Resources\HelpingMethods\HelpingMethodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHelpingMethods extends ListRecords
{
    protected static string $resource = HelpingMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

