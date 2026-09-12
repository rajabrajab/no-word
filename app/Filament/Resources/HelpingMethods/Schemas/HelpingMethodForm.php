<?php

namespace App\Filament\Resources\HelpingMethods\Schemas;

use App\Models\HelpingMethod;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HelpingMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('panel.name'))
                ->required()
                ->columnSpanFull(),

            Select::make('key')
                ->label(__('panel.helping_method_key'))
                ->helperText(__('panel.helping_method_key_hint'))
                ->options([
                    HelpingMethod::EXTRA_TIME => __('panel.helping_method_extra_time'),
                    HelpingMethod::CHANGE_QUESTION => __('panel.helping_method_change_question'),
                    HelpingMethod::ANSWER_HINT => __('panel.helping_method_answer_hint'),
                    HelpingMethod::REVEAL_ANSWER => __('panel.helping_method_reveal_answer'),
                ])
                ->unique(ignoreRecord: true)
                ->native(false)
                ->columnSpanFull(),

            TextInput::make('sort_order')
                ->label(__('panel.sort_order'))
                ->numeric()
                ->minValue(0)
                ->maxValue(255)
                ->default(0)
                ->columnSpanFull(),

            Textarea::make('description')
                ->label(__('panel.description'))
                ->rows(4)
                ->columnSpanFull(),

            FileUpload::make('icon')
                ->label(__('panel.icon'))
                ->image()
                ->disk('public')
                ->directory('helping_methods')
                ->visibility('public')
                ->columnSpanFull(),
        ]);
    }
}
