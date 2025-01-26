<?php

declare(strict_types=1);

namespace Kami\Cocktail\Metrics;

use Throwable;
use Prometheus\Storage\Redis;
use Prometheus\CollectorRegistry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class MetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CollectorRegistry::class, function (Application $app) {
            return new CollectorRegistry(new Redis([
                'host' => config('database.redis.default.host', 'localhost'),
                'port' => config('database.redis.default.port', 6379),
                'password' => config('database.redis.default.password', null),
                'user' => config('database.redis.default.username', null),
            ]));
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
