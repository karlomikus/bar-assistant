<?php

declare(strict_types=1);

namespace Kami\Cocktail\Metrics;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;

class SQLDuration extends BaseMetrics
{
    public function __invoke(string $route): void
    {
        $metric = $this->registry->getOrRegisterHistogram(
            $this->getDefaultNamespace(),
            'sql_duration_milliseconds',
            'SQL duration',
            ['route'],
            [100, 250, 500, 1000, 2500]
        );

        DB::listen(function (QueryExecuted $query) use ($metric, $route) {
            $metric->observe($query->time, [$route]);
        });
    }
}
