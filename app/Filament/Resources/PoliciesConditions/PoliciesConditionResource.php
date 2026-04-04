<?php

namespace App\Filament\Resources\PoliciesConditions;

use App\Filament\Resources\PoliciesConditions\Pages\CreatePoliciesCondition;
use App\Filament\Resources\PoliciesConditions\Pages\EditPoliciesCondition;
use App\Filament\Resources\PoliciesConditions\Pages\ListPoliciesConditions;
use App\Filament\Resources\PoliciesConditions\Schemas\PoliciesConditionForm;
use App\Filament\Resources\PoliciesConditions\Tables\PoliciesConditionsTable;
use App\Models\PoliciesCondition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PoliciesConditionResource extends Resource
{
    protected static ?string $model = PoliciesCondition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;
    protected static ?int    $navigationSort  = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.system_management');
    }

    public static function getTitle(): ?string
    {
        return __('panel.policiesConditions');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel.policiesConditions');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel.policiesConditions');
    }

    public static function getModelLabel(): string
    {
        return __('panel.policiesCondition');
    }


    public static function form(Schema $schema): Schema
    {
        return PoliciesConditionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PoliciesConditionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPoliciesConditions::route('/'),
            'create' => CreatePoliciesCondition::route('/create'),
            'edit' => EditPoliciesCondition::route('/{record}/edit'),
        ];
    }
}
