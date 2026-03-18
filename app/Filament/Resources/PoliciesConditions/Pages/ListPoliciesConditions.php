<?php

namespace App\Filament\Resources\PoliciesConditions\Pages;

use App\Filament\Resources\PoliciesConditions\PoliciesConditionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPoliciesConditions extends ListRecords
{
    protected static string $resource = PoliciesConditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
