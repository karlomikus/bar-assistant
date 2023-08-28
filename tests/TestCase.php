<?php

namespace Tests;

use Kami\Cocktail\BarContext;
use Kami\Cocktail\Models\Bar;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Kami\Cocktail\Models\UserRoleEnum;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setupBar(): Bar
    {
        $bar = Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id]);
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => $bar->id, 'user_id' => auth()->user()->id, 'user_role_id' => UserRoleEnum::Admin->value]);

        // TODO: Remove
        $this->app->singleton(BarContext::class, function () use ($bar) {
            return new BarContext($bar);
        });

        return $bar;
    }
}
