<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord:true)
                    ->maxLength(255)
                    ->label(__('panel.code')),

                Select::make('discount_type')
                    ->options([
                        'fixed' => __('panel.fixed'),
                        'percentage' => __('panel.percentage'),
                    ])
                    ->required()
                    ->label(__('panel.discountType')),

                TextInput::make('discount_value')
                    ->numeric()
                    ->required()
                    ->label(__('panel.discountValue')),

                TextInput::make('max_uses')
                    ->numeric()
                    ->nullable()
                    ->label(__('panel.maxUses')),

                DatePicker::make('valid_from')
                    ->label(__('panel.validFrom')),

                DatePicker::make('valid_to')
                    ->label(__('panel.validTo')),

                Toggle::make('is_active')
                    ->default(true)
                    ->label(__('panel.active')),
            ]);
    }
}
