<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Utensil;
use Illuminate\Auth\Access\HandlesAuthorization;

class UtensilPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return $user->isBarAdmin(bar()->id)
            || $user->isBarModerator(bar()->id);
    }

    public function show(User $user, Utensil $utensil): bool
    {
        return $user->isBarAdmin($utensil->bar_id);
    }

    public function edit(User $user, Utensil $utensil): bool
    {
        return $user->isBarAdmin($utensil->bar_id)
            || $user->isBarModerator($utensil->bar_id);
    }

    public function delete(User $user, Utensil $utensil): bool
    {
        return $user->isBarAdmin($utensil->bar_id)
            || $user->isBarModerator($utensil->bar_id);
    }
}
