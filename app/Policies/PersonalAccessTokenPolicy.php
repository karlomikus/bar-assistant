<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PersonalAccessTokenPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return $user->hasActiveSubscription();
    }
}
