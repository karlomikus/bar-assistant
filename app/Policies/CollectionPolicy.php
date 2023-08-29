<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Collection;
use Illuminate\Auth\Access\HandlesAuthorization;

class CollectionPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): bool|null
    {
        if (bar()->id && $user->isBarAdmin(bar()->id)) {
            return true;
        }

        return null;
    }

    public function create(User $user): bool
    {
        return $user->hasBarMembership(bar()->id);
    }

    public function show(User $user, Collection $collection): bool
    {
        return $user->memberships->contains('id', $collection->bar_membership_id);
    }

    public function edit(User $user, Collection $collection): bool
    {
        return $user->memberships->contains('id', $collection->bar_membership_id);
    }

    public function delete(User $user, Collection $collection): bool
    {
        return $user->memberships->contains('id', $collection->bar_membership_id);
    }
}
