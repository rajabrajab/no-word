<?php

namespace App\Filament\Resources\PoliciesConditions\Pages;

use App\Filament\Resources\PoliciesConditions\PoliciesConditionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPoliciesCondition extends EditRecord
{
    protected static string $resource = PoliciesConditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
