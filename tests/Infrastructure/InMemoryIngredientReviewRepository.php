<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Review\IngredientReview;
use BarAssistant\Domain\Review\IngredientReviewId;
use BarAssistant\Domain\Review\IngredientReviewRepository;

final class InMemoryIngredientReviewRepository implements IngredientReviewRepository
{
    /** @var array<int, IngredientReview> */
    private array $reviews = [];

    private int $sequence = 0;

    public function findById(IngredientReviewId $id): ?IngredientReview
    {
        return $this->reviews[$id->value] ?? null;
    }

    public function findForMemberAndIngredient(MemberId $memberId, int $ingredientId): ?IngredientReview
    {
        foreach ($this->reviews as $review) {
            if ($review->getIngredientId() === $ingredientId && $review->getMemberId()->equals($memberId)) {
                return $review;
            }
        }

        return null;
    }

    public function existsForMemberAndIngredient(MemberId $memberId, int $ingredientId): bool
    {
        return $this->findForMemberAndIngredient($memberId, $ingredientId) !== null;
    }

    public function save(IngredientReview $review): IngredientReview
    {
        if ($review->isTransient()) {
            $this->sequence++;
            $review->setId(new IngredientReviewId($this->sequence));
        }

        $this->reviews[$review->getId()->value] = $review;

        return $review;
    }

    public function delete(IngredientReviewId $id): void
    {
        unset($this->reviews[$id->value]);
    }

    /**
     * @return IngredientReview[]
     */
    public function all(): array
    {
        return array_values($this->reviews);
    }
}
