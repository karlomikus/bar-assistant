<?php

declare(strict_types=1);

namespace Kami\Cocktail;

use Kami\Cocktail\Models\Bar;

/**
 * A helper class that gets instanced when a HTTP request contains bar_id query parameter.
 * Combined with bar() helper method you can always get the current requested Bar model.
 * @see \Kami\Cocktail\Http\Middleware\EnsureRequestHasBarQuery
 */
final class BarContext
{
    public function __construct(private readonly Bar $currentBar)
    {
    }

    public function getBar(): Bar
    {
        return $this->currentBar;
    }
}
