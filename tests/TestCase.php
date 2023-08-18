<?php

namespace Tests;

use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Http\BarContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function useBar(Bar $bar)
    {
        $this->app->singleton(BarContext::class, function () use ($bar) {
            return new BarContext($bar);
        });
    }
}
