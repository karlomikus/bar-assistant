<?php

declare(strict_types=1);

namespace Kami\Cocktail\Metrics;

use Throwable;
use Prometheus\Storage\InMemory;
use Prometheus\CollectorRegistry;
use Illuminate\Support\Facades\Log;
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

    public function boot(): void
    {
        $metrics = collect([
            TotalBars::class,
            TotalActiveUsers::class,
        ]);

        $registry = $this->app->make(CollectorRegistry::class);

        $metrics->each(function (string $metricClassname) use ($registry) {
            try {
                $metric = new $metricClassname($registry);
                if (is_callable($metric)) {
                    $metric();
                }
            } catch (Throwable $e) {
                Log::error('Unable to register metric: ' . $metricClassname . '. Error: ' . $e->getMessage());
            }
        });
    }
}
