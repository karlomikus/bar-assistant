<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BarPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return $user->ownedBars->count() < config('bar-assistant.max_default_bars', 1);
    }
}
