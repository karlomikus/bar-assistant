<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BarPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return !$user->hasActiveSubscription() && $user->ownedBars->count() < config('bar-assistant.max_default_bars', 1);
    }

    public function show(User $user, Bar $bar): bool
    {
        return $user->hasBarMembership($bar->id);
    }

    public function edit(User $user, Bar $bar): bool
    {
        return $user->isBarAdmin($bar->id) || $user->isBarModerator($bar->id);
    }

    public function delete(User $user, Bar $bar): bool
    {
        return $user->id === $bar->owner()->id;
    }

    public function deleteMembership(User $user, Bar $bar): bool
    {
        return $user->isBarAdmin($bar->id);
    }
}
