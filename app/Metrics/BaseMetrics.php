<?php

declare(strict_types=1);

namespace Kami\Cocktail\Metrics;

use Prometheus\CollectorRegistry;

abstract class BaseMetrics
{
    public function __construct(protected readonly CollectorRegistry $registry)
    {
    }

    public function getDefaultNamespace(): string
    {
        return 'bar_assistant_api';
    }
}
