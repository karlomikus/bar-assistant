<?php

declare(strict_types=1);

namespace Kami\Cocktail\Events;

use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class IngredientUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly int $id, public readonly string $slug)
    {
        Log::debug('IngredientUpdated event fired', ['id' => $id, 'slug' => $slug]);
    }
}
