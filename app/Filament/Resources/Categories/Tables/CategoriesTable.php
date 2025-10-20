<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\Country;
use App\Filament\Resources\Categories\CategoryResource;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columnManager(false)
            ->columns([
                ImageColumn::make('image')
                ->size(60)
                ->circular()
                ->label(false),
                TextColumn::make('name')->label(__('panel.name'))->searchable(),
                TextColumn::make('country.name')->label(__('panel.country'))->searchable(),
            ])
            ->filters([

            ])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('addQuestions')
                        ->label(__('panel.add_questions') ?? 'Add Questions')
                        ->icon('heroicon-m-plus')
                        ->color('primary')
                        ->url(fn ($record) => CategoryResource::getUrl('bulkCreateQuestions', ['record' => $record])),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
