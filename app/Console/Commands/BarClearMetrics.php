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

    public function __construct(private readonly CollectorRegistry $registry)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->registry->wipeStorage();

        $this->info('Metrics storage cleared!');
    }
}
