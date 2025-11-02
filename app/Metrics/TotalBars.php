<?php

declare(strict_types=1);

namespace Kami\Cocktail\Metrics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Kami\Cocktail\Models\Enums\BarStatusEnum;

class TotalBars extends BaseMetrics
{
    public function __invoke(): void
    {
        $counts = Cache::remember('metrics_bass_total_bars', 60 * 24, fn () => DB::table('bars')->select(
            DB::raw("(CASE WHEN status IS NULL THEN 'active' ELSE status END) AS bar_status"),
            DB::raw('COUNT(*) AS total')
        )->groupBy('bar_status')->get()->keyBy('bar_status'));

        foreach (BarStatusEnum::cases() as $status) {
            $metric = $this->registry->getOrRegisterGauge(
                $this->getDefaultNamespace(),
                $status->value . '_bars_total',
                'Total number of ' . $status->value . ' bars'
            );
            $metric->set($counts[$status->value]->total ?? 0);
        }
    }
}
