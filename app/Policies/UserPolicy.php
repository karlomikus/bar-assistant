<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function list(User $user): bool
    {
        return $user->isBarAdmin(bar()->id)
            || $user->isBarModerator(bar()->id);
    }

    public function create(User $user): bool
    {
        return $user->isBarAdmin(bar()->id)
            || $user->isBarModerator(bar()->id);
    }

    public function show(User $user, User $model): bool
    {
        return $user->isBarAdmin(bar()->id)
            || $user->isBarModerator(bar()->id);
    }

    public function edit(User $user, User $model): bool
    {
        return $user->isBarAdmin(bar()->id)
            || $user->isBarModerator(bar()->id);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }
}
