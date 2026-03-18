<?php

namespace App\Filament\Resources\PoliciesConditions\Schemas;

use Filament\Schemas\Schema;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Tabs;

class PoliciesConditionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                  Tabs::make(__('panel.policies_tabs'))
                    ->tabs([
                        Tabs\Tab::make(__('panel.privacy_policy'))
                            ->schema([
                                TextInput::make('title.en')
                                    ->label(__('panel.title_en'))
                                    ->required(),

                                TextInput::make('title.ar')
                                    ->label(__('panel.title_ar'))
                                    ->required(),

                                RichEditor::make('content.en')
                                    ->label(__('panel.content_en'))
                                    ->required(),

                                RichEditor::make('content.ar')
                                    ->label(__('panel.content_ar'))
                                    ->required(),

                                Hidden::make('type')->default('privacy_policy'),
                            ]),

                        Tabs\Tab::make(__('panel.terms_conditions'))
                            ->schema([
                                TextInput::make('title.en')
                                    ->label(__('panel.title_en'))
                                    ->required(),

                                TextInput::make('title.ar')
                                    ->label(__('panel.title_ar'))
                                    ->required(),

                                RichEditor::make('content.en')
                                    ->label(__('panel.content_en'))
                                    ->required(),

                                RichEditor::make('content.ar')
                                    ->label(__('panel.content_ar'))
                                    ->required(),

                                Hidden::make('type')->default('terms_and_conditions'),
                            ]),
                    ])
                    ->columnSpanFull(),

                    Hidden::make('last_updated')
                        ->default(now()->toDateString()),
            ]);
    }
}
