<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Kami\Cocktail\Models\BarMembership;

class BarMembershipPolicy
{
    use HandlesAuthorization;

    public function list(User $user): bool
    {
        return $user->isBarAdmin(bar()->id)
            || $user->isBarModerator(bar()->id);
    }

    public function create(User $user): bool
    {
        return $user->hasActiveSubscription()
            && ($user->isBarAdmin(bar()->id) || $user->isBarModerator(bar()->id));
    }

    public function show(User $user): bool
    {
        return $user->isBarAdmin(bar()->id)
            || $user->isBarModerator(bar()->id);
    }

    public function edit(User $user): bool
    {
        return $user->isBarAdmin(bar()->id)
            || $user->isBarModerator(bar()->id);
    }

    public function delete(User $user, BarMembership $model): bool
    {
        return $user->id === $model->user_id
            || $user->isBarAdmin(bar()->id)
            || $user->isBarModerator(bar()->id);
    }
}
