<?php

namespace App\Filament\Resources\Countries\Tables;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('viewCategories')
                        ->label(__('panel.view_country_categories'))
                        ->icon('heroicon-m-rectangle-stack')
                        ->color('gray')
                        ->url(function ($record): string {
                            $base = CategoryResource::getUrl('index');

                            return $base.(str_contains($base, '?') ? '&' : '?').http_build_query([
                                'filters' => [
                                    'country_id' => [
                                        'value' => (string) $record->getKey(),
                                    ],
                                ],
                            ]);
                        }),
                    Action::make('viewQuestions')
                        ->label(__('panel.view_country_questions'))
                        ->icon('heroicon-m-queue-list')
                        ->color('gray')
                        ->url(function ($record): string {
                            $base = QuestionResource::getUrl('index');

                            return $base.(str_contains($base, '?') ? '&' : '?').http_build_query([
                                'filters' => [
                                    'country_id' => [
                                        'value' => (string) $record->getKey(),
                                    ],
                                ],
                            ]);
                        }),
                    Action::make('changeStatus')
                        ->label(fn ($record) => $record->is_active ? __('panel.make_inactive') : __('panel.make_active'))
                        ->icon(fn ($record) => $record->is_active ? Heroicon::XMark : Heroicon::Check)
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->action(fn ($record) => $record->update(['is_active' => ! $record->is_active]))
                        ->requiresConfirmation(),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
