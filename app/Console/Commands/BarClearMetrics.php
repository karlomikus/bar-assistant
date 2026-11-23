<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Prometheus\CollectorRegistry;

#[\Illuminate\Console\Attributes\Description('Clears metrics redis storage')]
#[\Illuminate\Console\Attributes\Signature('bar:clear-metrics')]
class BarClearMetrics extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (config('bar-assistant.metrics.enabled') !== true) {
            $this->error('Metrics are not enabled');

            return;
        }

        /** @var CollectorRegistry */
        $registry = resolve(CollectorRegistry::class);
        $registry->wipeStorage();

        $this->info('Metrics storage cleared!');
    }
}
