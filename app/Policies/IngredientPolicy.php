<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Auth\Access\HandlesAuthorization;

class IngredientPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): bool|null
    {
        if ($user->isBarOwner(bar())) {
            return true;
        }

        return null;
    }

    public function show(User $user, Ingredient $ingredient): bool
    {
        return $user->id === $ingredient->user_id && $user->hasBarMembership($ingredient->bar_id);
    }

    public function edit(User $user, Ingredient $ingredient): bool
    {
        return $user->id === $ingredient->user_id && $user->hasBarMembership($ingredient->bar_id);
    }

    public function delete(User $user, Ingredient $ingredient): bool
    {
        return $user->id === $ingredient->user_id && $user->hasBarMembership($ingredient->bar_id);
    }
}
