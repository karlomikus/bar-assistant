<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MenuPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->isBarAdmin(bar()->id) || $user->isBarModerator(bar()->id);
    }

    public function update(User $user): bool
    {
        return ($user->isBarAdmin(bar()->id) || $user->isBarModerator(bar()->id))
            && $user->hasActiveSubscription();
    }
}
