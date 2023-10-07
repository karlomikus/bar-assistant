<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\CocktailMethod;
use Illuminate\Auth\Access\HandlesAuthorization;

class CocktailMethodPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return $user->isBarAdmin(bar()->id)
            || $user->isBarModerator(bar()->id);
    }

    public function show(User $user, CocktailMethod $method): bool
    {
        return $user->hasBarMembership($method->bar_id);
    }

    public function edit(User $user, CocktailMethod $method): bool
    {
        return $user->isBarAdmin($method->bar_id)
            || $user->isBarModerator($method->bar_id);
    }

    public function delete(User $user, CocktailMethod $method): bool
    {
        return $user->isBarAdmin($method->bar_id)
            || $user->isBarModerator($method->bar_id);
    }
}
