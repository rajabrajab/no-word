<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class UsersPerDayChart extends ChartWidget
{
    protected ?string $heading = null;

    public function getHeading(): string|Htmlable|null
    {
        return __('panel.users_per_day');
    }

    protected function getData(): array
    {
        // Last 14 days
        $startDate = Carbon::now()->subDays(13)->startOfDay();
        $dates = collect(range(0, 13))->map(fn ($i) => $startDate->copy()->addDays($i));

        $counts = User::query()
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->where('created_at', '>=', $startDate)
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('c', 'd');

        $labels = $dates->map(fn ($d) => $d->format('Y-m-d'))->all();
        $data = $dates->map(fn ($d) => (int) ($counts[$d->toDateString()] ?? 0))->all();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('panel.users'),
                    'data' => $data,
                    'borderColor' => '#0d6efd',
                    'backgroundColor' => 'rgba(13,110,253,0.2)',
                    'tension' => 0.3,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}


