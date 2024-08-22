<?php

declare(strict_types=1);

namespace Tests;

use Laravel\Sanctum\Sanctum;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\UserRoleEnum;
use Kami\Cocktail\Models\BarMembership;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setupBar(): Bar
    {
        $bar = Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id]);
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => $bar->id, 'user_id' => auth()->user()->id, 'user_role_id' => UserRoleEnum::Admin->value]);

        return $bar;
    }

    public function setupBarMembership(UserRoleEnum $userRole = UserRoleEnum::Admin): BarMembership
    {
        $user = User::factory()->create();
        $bar = Bar::factory()->create(['created_user_id' => $user->id]);
        $membership = BarMembership::factory()
            ->recycle($user, $bar)
            ->create(['user_role_id' => $userRole->value]);

        return $membership;
    }

    public function actingAs(UserContract $user, $guard = null, array $abilities = ['*']): self
    {
        Sanctum::actingAs(
            $user,
            $abilities
        );

        return $this;
    }

    public function getFakeImageContent(string $extension = 'png')
    {
        $image = new \Imagick();
        $image->newImage(10, 10, new \ImagickPixel('red'));
        $image->setImageFormat($extension);

        return $image;
    }
}
