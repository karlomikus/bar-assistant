<?php

declare(strict_types=1);

use Kami\Cocktail\BarContext;
use Kami\Cocktail\Models\Bar;

if (!function_exists('bar')) {
    /**
     * Get current Bar model instance
     * Usually set through http query string
     *
     * @return Bar
     */
    function bar(): Bar
    {
        return app()->make(BarContext::class)->getBar();
    }
}

function barMembership()
{
    return app()->make(BarContext::class)->getCurrentUserBarMembership();
}
