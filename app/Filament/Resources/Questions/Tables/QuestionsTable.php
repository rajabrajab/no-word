<?php

namespace App\Filament\Resources\Questions\Tables;

use App\Models\Country;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#'),
                TextColumn::make('question')->label(__('panel.question')),
                TextColumn::make('answer')->label(__('panel.answer')),
                TextColumn::make('hint')->label(__('panel.hint')),
                TextColumn::make('score')->label(__('panel.score')),
                TextColumn::make('category.name')->label(__('panel.category')),
            ])
            ->filters([
                SelectFilter::make('country_id')
                    ->label(__('panel.country'))
                    ->options(fn (): array => Country::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if (blank($value)) {
                            return $query;
                        }

                        return $query->whereHas('category', fn (Builder $q) => $q->where('country_id', $value));
                    })
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category_id')
                    ->label(__('panel.category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
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
