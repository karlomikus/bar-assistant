<?php

declare(strict_types=1);

use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\BarContext;

if (! function_exists('bar')) {
    function bar(): Bar
    {
        return app()->make(BarContext::class)->getBar();
    }
}
