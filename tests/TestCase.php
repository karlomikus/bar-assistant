<?php

declare(strict_types=1);

namespace Tests;

use Jcupitt\Vips\Image;
use Laravel\Sanctum\Sanctum;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\Enums\UserRoleEnum;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setupBar(): Bar
    {
        /** @var \Kami\Cocktail\Models\User */
        $user = auth('sanctum')->user();
        $bar = Bar::factory()->create(['id' => 1, 'created_user_id' => $user->id]);
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => $bar->id, 'user_id' => $user->id, 'user_role_id' => UserRoleEnum::Admin->value]);

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

    /**
     * @param array<string> $abilities
     */
    public function actingAs(UserContract $user, $guard = null, array $abilities = ['*']): self
    {
        Sanctum::actingAs(
            $user,
            $abilities
        );

        return $this;
    }

    public function getFakeImageContent(string $extension = 'png'): string
    {
        $image = Image::black(10, 10)->bandjoin([0, 0, 0]);

        return $image->writeToBuffer('.' . $extension);
    }
}
