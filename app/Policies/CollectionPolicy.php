<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Collection;
use Illuminate\Auth\Access\HandlesAuthorization;

class CollectionPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        $hasMaxCollectionsForSubscription = !$user->hasActiveSubscription() && $user->getBarMembership(bar()->id)->cocktailCollections->count() >= 3;

        return $user->hasBarMembership(bar()->id)
            && !$hasMaxCollectionsForSubscription;
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
