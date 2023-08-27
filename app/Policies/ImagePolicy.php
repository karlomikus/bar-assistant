<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Image;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImagePolicy
{
    use HandlesAuthorization;

    public function show(User $user, Image $image): bool
    {
        $barId = $image->imageable?->bar_id ?? null;

        if (!$barId) {
            return $user->id === $image->created_user_id;
        }

        return $user->id === $image->created_user_id && $user->hasBarMembership($barId);
    }

    public function edit(User $user, Image $image): bool
    {
        $barId = $image->imageable?->bar_id ?? null;

        if (!$barId) {
            return $user->id === $image->created_user_id;
        }

        return $user->id === $image->created_user_id && $user->hasBarMembership($barId);
    }

    public function delete(User $user, Image $image): bool
    {
        $barId = $image->imageable?->bar_id ?? null;

        if (!$barId) {
            return $user->id === $image->created_user_id;
        }

        return $user->id === $image->created_user_id && $user->hasBarMembership($barId);
    }
}
