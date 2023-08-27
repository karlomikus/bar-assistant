<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Auth\Access\HandlesAuthorization;

class CocktailPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): bool|null
    {
        if ($user->isBarOwner(bar())) {
            return true;
        }

        return null;
    }

    public function show(User $user, Cocktail $cocktail): bool
    {
        return $user->hasBarMembership($cocktail->bar_id);
    }

    public function addNote(User $user, Cocktail $cocktail): bool
    {
        return $user->hasBarMembership($cocktail->bar_id);
    }

    public function edit(User $user, Cocktail $cocktail): bool
    {
        return $user->id === $cocktail->user_id && $user->hasBarMembership($cocktail->bar_id);
    }

    public function delete(User $user, Cocktail $cocktail): bool
    {
        return $user->id === $cocktail->user_id && $user->hasBarMembership($cocktail->bar_id);
    }

    public function rate(User $user, Cocktail $cocktail): bool
    {
        return $user->hasBarMembership($cocktail->bar_id);
    }
}
