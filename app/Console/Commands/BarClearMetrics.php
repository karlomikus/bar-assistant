<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Prometheus\CollectorRegistry;

class BarClearMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:clear-metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears metrics redis storage';

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
