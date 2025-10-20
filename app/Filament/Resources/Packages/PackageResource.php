<?php

namespace App\Filament\Resources\Packages;

use App\Filament\Resources\Packages\Pages\CreatePackage;
use App\Filament\Resources\Packages\Pages\EditPackage;
use App\Filament\Resources\Packages\Pages\ListPackages;
use App\Filament\Resources\Packages\Schemas\PackageForm;
use App\Filament\Resources\Packages\Tables\PackagesTable;
use App\Models\Package;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArchiveBox;

    public static function getNavigationLabel(): string
    {
        return __('panel.packages');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel.packages');
    }

    public static function getModelLabel(): string
    {
        return __('panel.package');
    }

    public static function getTitle(): string
    {
        return __('panel.packages');
    }

    public static function getNavigationGroup(): string
    {
        return __('panel.subscriptions&packages');
    }

    public static function getPluralNavigationGroup(): string
    {
        return __('panel.subscriptions&packages');
    }

    public static function form(Schema $schema): Schema
    {
        return PackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PackagesTable::configure($table);
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
            'index' => ListPackages::route('/'),
            'create' => CreatePackage::route('/create'),
            'edit' => EditPackage::route('/{record}/edit'),
        ];
    }
}
