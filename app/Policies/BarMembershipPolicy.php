<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\BarMembership;
use Illuminate\Auth\Access\HandlesAuthorization;

class BarMembershipPolicy
{
    use HandlesAuthorization;

    public function list(User $user): bool
    {
        return $user->isBarAdmin(bar()->id);
    }

    public function create(User $user): bool
    {
        return $user->hasActiveSubscription()
            && $user->isBarAdmin(bar()->id);
    }

    public function show(User $user, BarMembership $model): bool
    {
        return (int) $model->bar_id === bar()->id
            && $user->isBarAdmin(bar()->id);
    }

    public function edit(User $user, BarMembership $model): bool
    {
        return (int) $model->bar_id === bar()->id
            && $user->isBarAdmin(bar()->id);
    }

    public function delete(User $user, BarMembership $model): bool
    {
        return (int) $model->bar_id === bar()->id
            && ((int) $user->id === (int) $model->user_id || $user->isBarAdmin(bar()->id));
    }
}
