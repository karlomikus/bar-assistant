<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Calculator;
use Illuminate\Auth\Access\HandlesAuthorization;

class CalculatorPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return $user->isBarAdmin(bar()->id)
            || $user->isBarModerator(bar()->id);
    }

    public function show(User $user, Calculator $calculator): bool
    {
        return $user->hasBarMembership($calculator->bar_id);
    }

    public function edit(User $user, Calculator $calculator): bool
    {
        return $user->isBarAdmin($calculator->bar_id)
            || $user->isBarModerator($calculator->bar_id);
    }

    public function delete(User $user, Calculator $calculator): bool
    {
        return $user->isBarAdmin($calculator->bar_id)
            || $user->isBarModerator($calculator->bar_id);
    }
}
