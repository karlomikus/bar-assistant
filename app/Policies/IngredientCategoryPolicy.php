<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\IngredientCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class IngredientCategoryPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return $user->isBarAdmin(bar()->id)
            || $user->isBarModerator(bar()->id);
    }

    public function show(User $user, IngredientCategory $ingredientCategory): bool
    {
        return $user->hasBarMembership($ingredientCategory->bar_id);
    }

    public function edit(User $user, IngredientCategory $ingredientCategory): bool
    {
        return $user->isBarAdmin($ingredientCategory->bar_id)
            || $user->isBarModerator($ingredientCategory->bar_id);
    }

    public function delete(User $user, IngredientCategory $ingredientCategory): bool
    {
        return $user->isBarAdmin($ingredientCategory->bar_id)
            || $user->isBarModerator($ingredientCategory->bar_id);
    }
}
