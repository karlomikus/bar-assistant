<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http;

use Kami\Cocktail\Models\Bar;

class BarContext
{
    public function __construct(private readonly Bar $currentBar)
    {
    }

    public function getBar(): Bar
    {
        return $this->currentBar;
    }
}
