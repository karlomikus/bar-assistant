<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Jobs\RefreshSearchIndex;

#[\Illuminate\Console\Attributes\Description('Sync search engine indices with the latest Bar Assistant data')]
#[\Illuminate\Console\Attributes\Signature('bar:refresh-search {--c|clear : Clear indexes first}')]
class BarSearchRefresh extends Command
{
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
