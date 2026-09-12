<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columnManager(false)
            ->columns([
                ImageColumn::make('image')
                    ->disk('public')
                    ->size(60)
                    ->circular()
                    ->label(false),
                TextColumn::make('name')->label(__('panel.name'))->searchable(),
                TextColumn::make('description')
                    ->label(__('panel.description'))
                    ->limit(50)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('language')
                    ->label(__('panel.language'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('panel.language_'.$state)),
                TextColumn::make('country.name')->label(__('panel.country'))->searchable(),
            ])
            ->filters([
                SelectFilter::make('country_id')
                    ->label(__('panel.country'))
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('language')
                    ->label(__('panel.language'))
                    ->options([
                        Category::LANGUAGE_AR => __('panel.language_ar'),
                        Category::LANGUAGE_EN => __('panel.language_en'),
                        Category::LANGUAGE_BOTH => __('panel.language_both'),
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('viewQuestions')
                        ->label(__('panel.view_questions'))
                        ->icon('heroicon-m-queue-list')
                        ->color('gray')
                        ->url(function ($record): string {
                            $base = QuestionResource::getUrl('index');

                            return $base.(str_contains($base, '?') ? '&' : '?').http_build_query([
                                'filters' => [
                                    'category_id' => [
                                        'value' => (string) $record->getKey(),
                                    ],
                                ],
                            ]);
                        }),
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
