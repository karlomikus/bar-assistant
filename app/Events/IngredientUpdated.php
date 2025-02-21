<?php

declare(strict_types=1);

namespace Kami\Cocktail\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IngredientUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly int $id, public readonly ?string $slug = null)
    {
    }
}
