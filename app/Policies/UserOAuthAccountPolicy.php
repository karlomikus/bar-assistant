<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\UserOAuthAccount;
use Kami\Cocktail\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserOAuthAccountPolicy
{
    use HandlesAuthorization;

    public function show(User $user, UserOAuthAccount $account): bool
    {
        return $user->id === $account->user_id;
    }

    public function delete(User $user, UserOAuthAccount $account): bool
    {
        return $user->id === $account->user_id;
    }
}
