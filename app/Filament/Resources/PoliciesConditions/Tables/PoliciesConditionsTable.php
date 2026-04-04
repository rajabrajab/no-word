<?php

namespace App\Filament\Resources\PoliciesConditions\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class PoliciesConditionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('panel.title'))
                    ->formatStateUsing(fn ($state) => is_array($state) ? $state[app()->getLocale()] ?? '-' : $state)
                    ->searchable(),

                TextColumn::make('type')
                    ->label(__('panel.type'))
                    ->formatStateUsing(fn (string $state) => $state === 'privacy_policy'
                        ? __('panel.privacy_policy')
                        : __('panel.terms_conditions')),

                TextColumn::make('last_updated')
                    ->label(__('panel.last_updated'))
                    ->date()
                    ->sortable(),
            ])
             ->filters([
                SelectFilter::make('type')
                    ->label(__('panel.type'))
                    ->options([
                        'privacy_policy' => __('panel.privacy_policy'),
                        'terms_and_conditions' => __('panel.terms_conditions'),
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
