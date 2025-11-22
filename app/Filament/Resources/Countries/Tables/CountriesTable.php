<?php

namespace App\Filament\Resources\Countries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CountriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columnManager(false)
            ->columns([
                ImageColumn::make('image')
                ->disk('public')
                ->size(100)
                ->circular()
                ->label(false),
                TextColumn::make('name')->label(__('panel.name'))->searchable(),
                TextColumn::make('is_active')
                    ->label(__('panel.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('panel.active') : __('panel.inactive'))
                    ->color(fn ($state) => $state ? 'success' : 'danger')
            ])

            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('changeStatus')
                    ->label(fn ($record) => $record->is_active ? __('panel.make_inactive') : __('panel.make_active'))
                    ->icon(fn ($record) => $record->is_active ? Heroicon::XMark : Heroicon::Check)
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(fn ($record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
