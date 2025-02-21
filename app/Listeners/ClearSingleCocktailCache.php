<?php

declare(strict_types=1);

namespace Kami\Cocktail\Listeners;

use Illuminate\Support\Facades\Cache;
use Kami\Cocktail\Events\CocktailUpdated;
use Kami\Cocktail\Services\CacheService;

class ClearSingleCocktailCache
{
    public function __construct()
    {
    }

    public function handle(CocktailUpdated $event): void
    {
        $cacheKeys = [
            sprintf(CacheService::SIMILAR_COCKTAILS, $event->id),
        ];

        foreach ($cacheKeys as $cacheKey) {
            Cache::forget($cacheKey);
        }
    }
}
