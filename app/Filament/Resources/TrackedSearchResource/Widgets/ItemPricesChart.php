<?php

namespace App\Filament\Resources\TrackedSearchResource\Widgets;

use App\Models\TrackedSearch;
use Cknow\Money\Money;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Illuminate\Support\Collection;
use Illuminate\Support\Js;

class ItemPricesChart extends ChartWidget
{
    protected static ?string $heading = null;

    protected int|string|array $columnSpan = 2;

    public TrackedSearch $trackedSearch;

    protected function getData(): array
    {

        $avg = $this->getTrendQuery()->average('prices.value');
        $min = $this->getTrendQuery()->min('prices.value');
        $max = $this->getTrendQuery()->max('prices.value');

        $labels = $avg->pluck('date')->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Prezzo medio',
                    'data' => self::getTrendData($avg),
                ],
                [
                    'label' => 'Prezzo minimo',
                    'data' => self::getTrendData($min),
                ],
                [
                    'label' => 'Prezzo massimo',
                    'data' => self::getTrendData($max),
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            // Animazioni e interazioni
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],

            // Configurazione dei plugin
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'Andamento Prezzi ultimi 12 mesi',
                    'padding' => 20,
                    'font' => [
                        'size' => 16,
                        'weight' => '500',
                    ],
                ],
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                        'boxWidth' => 12,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'padding' => 10,
                    'callbacks' => [
                        // Formatter del valore con € e 2 decimali
                        'label' => new Js('function(context){const val = context.parsed.y;return `${context.dataset.label}: €${val.toFixed(2)}`;}'),
                    ],
                ],
            ],

            // Configurazione degli assi
            'scales' => [
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Mese',
                        'font' => ['size' => 14],
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Prezzo (€)',
                        'font' => ['size' => 14],
                    ],
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                        'borderDash' => [5, 5],
                    ],
                    'ticks' => [
                        'beginAtZero' => false,
                        'callback' => "function(val) { return '€' + val; }",
                    ],
                ],
            ],

            // Stile degli elementi (linee e punti)
            'elements' => [
                'line' => [
                    'tension' => 0.2,   // curvatura della linea
                    'borderWidth' => 3,
                ],
                'point' => [
                    'radius' => 4,
                    'hoverRadius' => 6,
                    'hitRadius' => 8,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getTrendQuery(): Trend
    {
        return Trend::query($this->trackedSearch->prices()->getQuery())
            ->between(
                start: now()->subMonths(2),
                end: now()->addMonths(4)
            )
            ->perMonth()
            ->dateColumn('prices.created_at');
    }

    protected static function getTrendData(Collection $data): Collection
    {
        return $data
            ->pluck('aggregate')
            ->map(fn (float $price) => (new Money((int) $price))->formatByDecimal());
    }
}
