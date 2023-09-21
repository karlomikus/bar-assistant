<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Glass;
use Illuminate\Auth\Access\HandlesAuthorization;

class GlassPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return $user->isBarAdmin(bar()->id)
            || $user->isBarModerator(bar()->id);
    }

    public function show(User $user, Glass $glass): bool
    {
        return $user->hasBarMembership($glass->bar_id);
    }

    public function edit(User $user, Glass $glass): bool
    {
        return $user->isBarAdmin($glass->bar_id)
            || $user->isBarModerator($glass->bar_id);
    }

    public function delete(User $user, Glass $glass): bool
    {
        return $user->isBarAdmin($glass->bar_id)
            || $user->isBarModerator($glass->bar_id);
    }
}
