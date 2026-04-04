<?php

namespace App\Filament\Resources\Coupons\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columnManager(false)
            ->columns([
                TextColumn::make('code')->searchable()->label(__('panel.code')),
                TextColumn::make('discount_type')
                ->label(__('panel.discountType'))
                ->getStateUsing(function ($record) {
                    return match ($record->discount_type) {
                        'percentage' => __('panel.percentage'),
                        'fixed' => __('panel.fixed'),
                        default => $record->discount_type,
                    };
                }),
                TextColumn::make('discount_value')->label(__('panel.discountValue')),
                TextColumn::make('valid_from')->date()->label(__('panel.validFrom')),
                TextColumn::make('valid_to')->date()->label(__('panel.validTo')),
                TextColumn::make('max_uses')->label(__('panel.maxUses')),
                TextColumn::make('used_count')->label(__('panel.usedCount')),
                TextColumn::make('is_active')->badge()->color(fn ($state) => $state ? 'success' : 'danger')->formatStateUsing(fn ($state) => $state ? __('panel.active') : __('panel.inactive'))->label(__('panel.status')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('changeStatus')
                        ->label(fn ($record) => $record->is_active ? __('panel.make_inactive') : __('panel.make_active'))
                        ->icon(fn ($record) => $record->is_active ? Heroicon::XMark : Heroicon::Check)
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->action(fn ($record) => $record->update(['is_active' => ! $record->is_active])),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ]);
    }
}
