<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Review;

use BarAssistant\Domain\Bar\MemberId;

interface IngredientReviewRepository
{
    public function findById(IngredientReviewId $id): ?IngredientReview;

    public function findForMemberAndIngredient(MemberId $memberId, int $ingredientId): ?IngredientReview;

    public function existsForMemberAndIngredient(MemberId $memberId, int $ingredientId): bool;

    /**
     * @param string[] $tasteDescriptorNames
     */
    public function save(IngredientReview $review, array $tasteDescriptorNames = []): IngredientReview;

    public function delete(IngredientReviewId $id): void;
}
