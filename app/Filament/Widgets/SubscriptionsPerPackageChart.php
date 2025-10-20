<?php

namespace App\Filament\Widgets;

use App\Models\Package;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class SubscriptionsPerPackageChart extends ChartWidget
{
    protected ?string $heading = null;

    public function getHeading(): string|Htmlable|null
    {
        return __('panel.subscriptions_per_package');
    }

    protected function getData(): array
    {
        $packages = Package::query()
            ->orderByDesc('subscription_count')
            ->limit(10)
            ->get(['name', 'subscription_count']);

        $labels = $packages->pluck('name')->all();
        $data = $packages->pluck('subscription_count')->map(fn ($v) => (int) $v)->all();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('panel.subscription_count'),
                    'data' => $data,
                    'backgroundColor' => 'rgba(255,193,7,0.5)', // bootstrap warning
                    'borderColor' => '#ffc107',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}


