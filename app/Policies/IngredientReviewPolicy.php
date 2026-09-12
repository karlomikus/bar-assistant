<?php

declare(strict_types=1);

namespace Kami\Cocktail\Policies;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Ingredient;
use BarAssistant\Domain\Review\IngredientReview;
use Illuminate\Auth\Access\HandlesAuthorization;

class IngredientReviewPolicy
{
    use HandlesAuthorization;

    public function update(User $user, IngredientReview $review, Ingredient $ingredient): bool
    {
        return $this->isAuthorOrAdmin($user, $review, $ingredient);
    }

    public function delete(User $user, IngredientReview $review, Ingredient $ingredient): bool
    {
        return $this->isAuthorOrAdmin($user, $review, $ingredient);
    }

    private function isAuthorOrAdmin(User $user, IngredientReview $review, Ingredient $ingredient): bool
    {
        $membership = $user->getBarMembership((int) $ingredient->bar_id);

        if ($membership === null) {
            return false;
        }

        return $membership->id === $review->getMemberId()->value
            || $user->isBarAdmin((int) $ingredient->bar_id);
    }
}
