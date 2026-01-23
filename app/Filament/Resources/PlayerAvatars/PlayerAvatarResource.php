<?php

namespace App\Filament\Resources\PlayerAvatars;

use App\Filament\Resources\PlayerAvatars\Pages\CreatePlayerAvatar;
use App\Filament\Resources\PlayerAvatars\Pages\EditPlayerAvatar;
use App\Filament\Resources\PlayerAvatars\Pages\ListPlayerAvatars;
use App\Filament\Resources\PlayerAvatars\Schemas\PlayerAvatarForm;
use App\Filament\Resources\PlayerAvatars\Tables\PlayerAvatarsTable;
use App\Models\PlayerAvatar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PlayerAvatarResource extends Resource
{
    protected static ?string $model = PlayerAvatar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserCircle;

    public static function getNavigationLabel(): string
    {
        return __('panel.player_avatars');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel.player_avatars');
    }

    public static function getModelLabel(): string
    {
        return __('panel.player_avatar');
    }

    public static function getTitle(): string
    {
        return __('panel.player_avatars');
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
        return PlayerAvatarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlayerAvatarsTable::configure($table);
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
            'index' => ListPlayerAvatars::route('/'),
            'create' => CreatePlayerAvatar::route('/create'),
            'edit' => EditPlayerAvatar::route('/{record}/edit'),
        ];
    }
}

