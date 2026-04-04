<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Notifications\Notification;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
           ->query(
                fn () => User::query()->where('type', '!=', 'admin')
            )
            ->columnManager(false)
            ->columns([
                ImageColumn::make('profile_image')
                    ->disk('public')
                    ->label(false)
                    ->circular()
                    ->height(40)
                    ->width(40)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random&length=2')
                    ->size(40),
                TextColumn::make('name')->label(__('panel.name'))->searchable(),
                TextColumn::make('email')->label(__('panel.email'))->searchable(),
                TextColumn::make('normalized')->label(__('panel.phone'))->searchable(),
                TextColumn::make('is_blocked')->label(__('panel.status'))->badge()
                ->formatStateUsing(fn ($state) => $state ? __('panel.blocked') : __('panel.active'))
                ->color(fn ($state) => $state ? 'danger' : 'success'),
                TextColumn::make('subscription.package.name')
                ->label(__('panel.selected_package'))
                ->default(__('panel.no_package'))
                ->searchable(),
                TextColumn::make('joined_at')->date()->label(__('panel.joined_at'))
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    DeleteAction::make(),
                    Action::make('blockUser')
                        ->label(fn ($record) => $record->is_blocked ? __('panel.unblock_user') : __('panel.block_user'))
                        ->icon(fn ($record) => $record->is_blocked ? 'heroicon-o-check' : 'heroicon-o-x-mark')
                        ->color(fn ($record) => $record->is_blocked ? 'success' : 'danger')
                        ->requiresConfirmation()
                        ->action(function ($record, $livewire) {
                            $record->is_blocked = ! $record->is_blocked;
                            $record->save();

                            Notification::make()
                                ->title($record->is_blocked ? __('panel.user_blocked') : __('panel.user_unblocked'))
                                ->success()
                                ->send();

                            $livewire->dispatch('$refresh');
                        }),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ]);
    }
}
