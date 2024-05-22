<?php

declare(strict_types=1);

namespace Kami\Cocktail\Jobs;

use Illuminate\Bus\Queueable;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Kami\Cocktail\Search\SearchActionsAdapter;

class RefreshSearchIndex implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly bool $shouldClearIndexes = false)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SearchActionsAdapter $searchActions): void
    {
        $searchActions = $searchActions->getActions();

        // Clear indexes
        if ($this->shouldClearIndexes) {
            Log::info('Clearing search indexes');
            Artisan::call('scout:flush', ['model' => Cocktail::class]);
            Artisan::call('scout:flush', ['model' => Ingredient::class]);
        }

        // Update settings
        Log::info('Updating search settings');
        $searchActions->updateIndexSettings();

        Log::info('Building search indexes');

        Artisan::call('scout:import', ['model' => Cocktail::class]);
        Artisan::call('scout:import', ['model' => Ingredient::class]);

        Log::info('Search indexes updated');
    }
}
