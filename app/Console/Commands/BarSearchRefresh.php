<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\SearchActions;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Artisan;

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
        // $this->info('Removing cocktails and ingredients index...');
        // Artisan::call('scout:flush', ['model' => "Kami\Cocktail\Models\Cocktail"]);
        // Artisan::call('scout:flush', ['model' => "Kami\Cocktail\Models\Ingredient"]);

        // Update settings
        $this->info('Updating search index settings...');
        SearchActions::updateIndexSettings();

        $this->info('Syncing cocktails and ingredients to meilisearch...');
        Artisan::call('scout:import', ['model' => Cocktail::class]);
        Artisan::call('scout:import', ['model' => Ingredient::class]);

        // Site search model imports
        /** @var \Laravel\Scout\Engines\MeiliSearchEngine|\MeiliSearch\Client */
        $engine = app(\Laravel\Scout\EngineManager::class)->engine();
        Ingredient::cursor()->chunk(500)->each(function ($chunk) use ($engine) {
            $chunk->each(function ($model) use ($engine) {
                $engine->index('site_search_index')->addDocuments([
                    $model->toSiteSearchArray()
                ], 'key');
            });
        });
        Cocktail::cursor()->chunk(500)->each(function ($chunk) use ($engine) {
            $chunk->each(function ($model) use ($engine) {
                $engine->index('site_search_index')->addDocuments([
                    $model->toSiteSearchArray()
                ], 'key');
            });
        });

        return Command::SUCCESS;
    }
}
