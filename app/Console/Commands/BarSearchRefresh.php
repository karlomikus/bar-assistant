<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Jobs\RefreshSearchIndex;

class BarSearchRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:refresh-search {--c|clear : Clear indexes first}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync search engine indices with the latest Bar Assistant data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('clear')) {
            $this->info('Clearing index and syncing...');
        } else {
            $this->info('Syncing search index...');
        }

        RefreshSearchIndex::dispatch((bool) $this->option('clear'));

        return Command::SUCCESS;
    }
}
