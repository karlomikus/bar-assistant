<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\IngredientCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class IngredientCategoryPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): bool|null
    {
        if (bar()->id && $user->isBarAdmin(bar()->id)) {
            return true;
        }

        return null;
    }

    public function show(User $user, IngredientCategory $ingredientCategory): bool
    {
        return $user->hasBarMembership($ingredientCategory->bar_id);
    }

    public function edit(User $user, IngredientCategory $ingredientCategory): bool
    {
        return $user->hasBarMembership($ingredientCategory->bar_id);
    }

    public function delete(User $user, IngredientCategory $ingredientCategory): bool
    {
        return $user->hasBarMembership($ingredientCategory->bar_id);
    }
}
