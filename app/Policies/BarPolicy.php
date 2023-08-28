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
        return $user->ownedBars->count() < config('bar-assistant.max_default_bars', 1);
    }

    public function delete(User $user, Bar $bar): bool
    {
        return $user->id === $bar->created_user_id;
    }
}
