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
        if (!config('bar-assistant.enable_billing')) {
            return true;
        }

        $barCount = $user->ownedBars->count();

        return (!$user->hasActiveSubscription() && $barCount < (int) config('bar-assistant.max_default_bars'))
            || ($user->hasActiveSubscription() && $barCount < (int) config('bar-assistant.max_premium_bars'));
    }

    public function show(User $user, Bar $bar): bool
    {
        return $user->hasBarMembership($bar->id);
    }

    public function edit(User $user, Bar $bar): bool
    {
        return $user->id === $bar->owner()->id
            || $user->isBarAdmin($bar->id);
    }

    public function delete(User $user, Bar $bar): bool
    {
        return $user->id === $bar->owner()->id;
    }

    public function deleteMembership(User $user, Bar $bar): bool
    {
        return $user->id === $bar->owner()->id || $user->isBarAdmin($bar->id);
    }

    public function transfer(User $user, Bar $bar): bool
    {
        return $user->id === $bar->owner()->id;
    }

    public function activate(User $user, Bar $bar): bool
    {
        return $user->id === $bar->owner()->id;
    }

    public function deactivate(User $user, Bar $bar): bool
    {
        return $user->id === $bar->owner()->id;
    }

    public function createExport(User $user, Bar $bar): bool
    {
        return $user->id === $bar->created_user_id;
    }

    public function access(User $user, Bar $bar): bool
    {
        return $user->hasBarMembership($bar->id) && $bar->isAccessible();
    }
}
