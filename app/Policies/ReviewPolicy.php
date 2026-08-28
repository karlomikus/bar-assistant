<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use BarAssistant\Domain\Review\Review;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Review $review, Cocktail $cocktail): bool
    {
        $membership = $user->getBarMembership($cocktail->bar_id);

        return $membership !== null
            && $membership->id === $review->getMemberId()->value;
    }

    public function delete(User $user, Review $review, Cocktail $cocktail): bool
    {
        $membership = $user->getBarMembership($cocktail->bar_id);

        if ($membership === null) {
            return false;
        }

        return $membership->id === $review->getMemberId()->value
            || $user->isBarAdmin($cocktail->bar_id);
    }
}
