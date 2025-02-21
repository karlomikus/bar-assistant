<?php

declare(strict_types=1);

namespace Kami\Cocktail\Listeners;

use Illuminate\Support\Facades\Cache;
use Kami\Cocktail\Events\IngredientUpdated;
use Kami\Cocktail\Services\CacheService;

class ClearSingleIngredientCache
{
    public function __construct()
    {
    }

    public function handle(IngredientUpdated $event): void
    {
        Cache::tags([sprintf(CacheService::TAG_INGREDIENT_SHOW, $event->id)])->flush();
        if ($event->slug) {
            Cache::tags([sprintf(CacheService::TAG_INGREDIENT_SHOW, $event->slug)])->flush();
        }
    }
}
