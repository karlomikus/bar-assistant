<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\Note;
use Kami\Cocktail\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotePolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): bool|null
    {
        if ($user->isBarOwner(bar())) {
            return true;
        }

        return null;
    }

    public function show(User $user, Note $note): bool
    {
        return $user->id === $note->user_id;
    }

    public function edit(User $user, Note $note): bool
    {
        return $user->id === $note->user_id;
    }

    public function delete(User $user, Note $note): bool
    {
        return $user->id === $note->user_id;
    }
}
