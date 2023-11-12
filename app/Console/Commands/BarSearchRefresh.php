<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Artisan;
use Kami\Cocktail\Search\SearchActionsAdapter;

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
    protected $description = 'Sync search engine index with the latest Bar Assistant data';

    public function __construct(private readonly SearchActionsAdapter $searchActions)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $searchActions = $this->searchActions->getActions();

        // Clear indexes
        if ($this->option('clear')) {
            $this->info('Flushing site search, cocktails and ingredients index...');
            Artisan::call('scout:flush', ['model' => Cocktail::class]);
            Artisan::call('scout:flush', ['model' => Ingredient::class]);
        }

        // Update settings
        $this->info('Updating search index settings...');
        $searchActions->updateIndexSettings();

        $this->info('Syncing cocktails and ingredients to meilisearch...');
        Artisan::call('scout:import', ['model' => Cocktail::class]);
        Artisan::call('scout:import', ['model' => Ingredient::class]);

        return Command::SUCCESS;
    }
}
