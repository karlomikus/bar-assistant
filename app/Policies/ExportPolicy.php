<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Export;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExportPolicy
{
    use HandlesAuthorization;

    public function delete(User $user, Export $token): bool
    {
        return $token->created_user_id === $user->id;
    }

    public function download(User $user, Export $token): bool
    {
        return $token->created_user_id === $user->id;
    }
}
