<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Country;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\Question;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Lang;

class AdminStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(__('panel.users'), number_format(User::count()))
                ->icon('heroicon-o-user'),
            Stat::make(__('panel.questions'), number_format(Question::count()))
                ->icon('heroicon-o-question-mark-circle'),
            Stat::make(__('panel.packages'), number_format(Package::count()))
                ->icon('heroicon-o-archive-box'),
            Stat::make(__('panel.coupons'), number_format(Coupon::count()))
                ->icon('heroicon-o-ticket'),
            Stat::make(__('panel.categories'), number_format(Category::count()))
                ->icon('heroicon-o-rectangle-stack'),
            Stat::make(__('panel.countries'), number_format(Country::count()))
                ->icon('heroicon-o-globe-asia-australia'),
        ];
    }
}


