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

    /** @var array<int, string[]> */
    private array $descriptors = [];

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

    public function save(IngredientReview $review, array $tasteDescriptorNames = []): IngredientReview
    {
        if ($review->isTransient()) {
            $this->sequence++;
            $review->setId(new IngredientReviewId($this->sequence));
        }

        $this->reviews[$review->getId()->value] = $review;
        $this->descriptors[$review->getId()->value] = $tasteDescriptorNames;

        return $review;
    }

    public function delete(IngredientReviewId $id): void
    {
        unset($this->reviews[$id->value], $this->descriptors[$id->value]);
    }

    /**
     * @return IngredientReview[]
     */
    public function all(): array
    {
        return array_values($this->reviews);
    }

    /**
     * @return string[]
     */
    public function descriptorsFor(IngredientReviewId $id): array
    {
        return $this->descriptors[$id->value] ?? [];
    }
}
