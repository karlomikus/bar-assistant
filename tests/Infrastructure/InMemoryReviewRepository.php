<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Review\Review;
use BarAssistant\Domain\Review\ReviewId;
use BarAssistant\Domain\Review\ReviewRead;
use BarAssistant\Domain\Review\ReviewRepository;

final class InMemoryReviewRepository implements ReviewRepository
{
    /** @var array<int, Review> */
    private array $reviews = [];

    private int $sequence = 0;

    public function findById(ReviewId $id): ?Review
    {
        return $this->reviews[$id->value] ?? null;
    }

    public function save(Review $review): Review
    {
        if ($review->isTransient()) {
            $this->sequence++;
            $review->setId(new ReviewId($this->sequence));
        }

        $this->reviews[$review->getId()->value] = $review;

        return $review;
    }

    public function delete(ReviewId $id): void
    {
        unset($this->reviews[$id->value]);
    }

    public function existsForMemberAndCocktail(MemberId $memberId, int $cocktailId): bool
    {
        foreach ($this->reviews as $review) {
            if ($review->getCocktailId() === $cocktailId && $review->getMemberId()->equals($memberId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Review[]
     */
    public function all(): array
    {
        return array_values($this->reviews);
    }

    private function toRead(Review $review): ReviewRead
    {
        $timestamps = $review->getRecordTimestamps();

        return new ReviewRead(
            id: $review->getId() ?? new ReviewId(0),
            cocktailId: $review->getCocktailId(),
            memberId: $review->getMemberId(),
            content: $review->getContent()->value,
            rating: null,
            authorUserId: 1,
            authorName: 'Test Author',
            createdAt: $timestamps->getCreatedAt(),
            updatedAt: $timestamps->getUpdatedAt(),
        );
    }
}
