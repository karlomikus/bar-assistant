<?php

declare(strict_types=1);

namespace Kami\Cocktail\Metrics;

class ApiRequestDuration extends BaseMetrics
{
    public function __invoke(float $time, string $route, string $method, int $status): void
    {
        $metric = $this->registry->getOrRegisterHistogram(
            $this->getDefaultNamespace(),
            'api_request_processing_milliseconds',
            'Time spent processing API request',
            ['route', 'method', 'status'],
            [100, 250, 500, 1000, 2500, 5000, 10000]
        );

        $metric->observe($time * 1000, ['/' . $route, $method, $status]);
    }
}
