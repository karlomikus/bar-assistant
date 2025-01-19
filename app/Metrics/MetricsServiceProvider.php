<?php

declare(strict_types=1);

namespace Kami\Cocktail\Metrics;

use Prometheus\Storage\InMemory;
use Prometheus\CollectorRegistry;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class MetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CollectorRegistry::class, function (Application $app) {
            return new CollectorRegistry(new InMemory());
        });
    }
}
