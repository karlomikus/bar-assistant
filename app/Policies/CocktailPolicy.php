<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Auth\Access\HandlesAuthorization;

class CocktailPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
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
