<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Auth\Access\HandlesAuthorization;

class CocktailPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->isAdmin()) {
            return true;
        }
    }

    public function edit(User $user, Cocktail $cocktail): bool
    {
        return $user->id === $cocktail->user_id;
    }

    public function delete(User $user, Cocktail $cocktail): bool
    {
        return $user->id === $cocktail->user_id;
    }
}
