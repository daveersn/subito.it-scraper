<?php

namespace App\Filament\Resources\TrackedSearchResource\Widgets;

use App\Models\TrackedSearch;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;

class ItemPricesChart extends ChartWidget
{
    protected static ?string $heading = null;

    public TrackedSearch $trackedSearch;

    protected function getData(): array
    {
        $trend = Trend::query($this->trackedSearch->prices()->getQuery())
            ->between(
                start: now()->subMonths(2),
                end: now()->addMonths(4)
            )
            ->perMonth()
            ->dateColumn('prices.created_at')
            ->average('prices.value');

        $labels = $trend->pluck('date')->toArray();
        $data = $trend
            ->pluck('aggregate')
            ->map(fn (float $price) => (int) $price);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Prezzo medio',
                    'data' => $data,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
