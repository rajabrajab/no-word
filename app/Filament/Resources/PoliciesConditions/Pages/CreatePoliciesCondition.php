<?php

namespace App\Filament\Resources\PoliciesConditions\Pages;

use App\Filament\Resources\PoliciesConditions\PoliciesConditionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePoliciesCondition extends CreateRecord
{
    protected static string $resource = PoliciesConditionResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
