<?php

namespace App\Filament\Resources\HelpingMethods;

use App\Filament\Resources\HelpingMethods\Pages\CreateHelpingMethod;
use App\Filament\Resources\HelpingMethods\Pages\EditHelpingMethod;
use App\Filament\Resources\HelpingMethods\Pages\ListHelpingMethods;
use App\Filament\Resources\HelpingMethods\Schemas\HelpingMethodForm;
use App\Filament\Resources\HelpingMethods\Tables\HelpingMethodsTable;
use App\Models\HelpingMethod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HelpingMethodResource extends Resource
{
    protected static ?string $model = HelpingMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::LightBulb;

    public static function getNavigationLabel(): string
    {
        return __('panel.helping_methods');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel.helping_methods');
    }

    public static function getModelLabel(): string
    {
        return __('panel.helping_method');
    }

    public static function getTitle(): string
    {
        return __('panel.helping_methods');
    }

     public static function getNavigationGroup(): string
    {
        return __('panel.game_settings');
    }

    public static function getPluralNavigationGroup(): string
    {
        return __('panel.game_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return HelpingMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HelpingMethodsTable::configure($table);
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
            'index' => ListHelpingMethods::route('/'),
            'create' => CreateHelpingMethod::route('/create'),
            'edit' => EditHelpingMethod::route('/{record}/edit'),
        ];
    }
}

