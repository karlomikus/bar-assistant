<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Auth\Access\HandlesAuthorization;

class IngredientPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        $barId = bar()->id;

        return $user->isBarAdmin($barId)
            || $user->isBarModerator($barId)
            || $user->isBarGeneral($barId);
    }

    public function show(User $user, Ingredient $ingredient): bool
    {
        return $user->hasBarMembership($ingredient->bar_id);
    }

    public function edit(User $user, Ingredient $ingredient): bool
    {
        return ($user->id === $ingredient->created_user_id && $user->hasBarMembership($ingredient->bar_id))
            || $user->isBarAdmin($ingredient->bar_id)
            || $user->isBarModerator($ingredient->bar_id);
    }

    public function delete(User $user, Ingredient $ingredient): bool
    {
        return ($user->id === $ingredient->created_user_id && $user->hasBarMembership($ingredient->bar_id))
            || $user->isBarAdmin($ingredient->bar_id)
            || $user->isBarModerator($ingredient->bar_id);
    }
}
