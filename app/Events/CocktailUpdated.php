<?php

declare(strict_types=1);

namespace Kami\Cocktail\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class CocktailUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly int $id)
    {
    }
}
