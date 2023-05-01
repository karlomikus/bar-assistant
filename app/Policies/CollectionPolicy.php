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
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function show(User $user, Collection $collection): bool
    {
        return $user->id === $collection->user_id;
    }

    public function edit(User $user, Collection $collection): bool
    {
        return $user->id === $collection->user_id;
    }

    public function delete(User $user, Collection $collection): bool
    {
        return $user->id === $collection->user_id;
    }
}
