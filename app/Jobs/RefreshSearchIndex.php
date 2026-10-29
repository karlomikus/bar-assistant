<?php

declare(strict_types=1);

namespace Kami\Cocktail\Jobs;

use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Contracts\Queue\ShouldQueue;

class RefreshSearchIndex implements ShouldQueue
{
    use \Illuminate\Foundation\Queue\Queueable;

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
    public function handle(): void
    {
        // Clear indexes
        if ($this->shouldClearIndexes) {
            Log::info('Clearing search indexes');
            Artisan::call('scout:flush', ['model' => Cocktail::class]);
            Artisan::call('scout:flush', ['model' => Ingredient::class]);
        }

        Log::info('Building search indexes');

        Artisan::call('scout:import', ['model' => Cocktail::class]);
        Artisan::call('scout:import', ['model' => Ingredient::class]);

        Log::info('Search indexes updated');
    }
}
