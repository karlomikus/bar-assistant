<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Kami\Cocktail\SearchActions;

class BarSearchRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:refresh-search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh search index';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Clear indexes
        // SearchActions::flushSearchIndex(); // TODO: Create method to import site_index
        $this->info('Removing cocktails and ingredients index...');
        Artisan::call('scout:flush', ['model' => "Kami\Cocktail\Models\Cocktail"]);
        Artisan::call('scout:flush', ['model' => "Kami\Cocktail\Models\Ingredient"]);

        // Update settings
        $this->info('Updating index settings...');
        SearchActions::updateIndexSettings();
        
        $this->info('Importing cocktails and ingredients...');
        Artisan::call('scout:import', ['model' => "Kami\Cocktail\Models\Cocktail"]);
        Artisan::call('scout:import', ['model' => "Kami\Cocktail\Models\Ingredient"]);

        return Command::SUCCESS;
    }
}
