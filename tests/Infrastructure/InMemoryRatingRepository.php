<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Rating\Rating;
use BarAssistant\Domain\Rating\RatingId;
use BarAssistant\Domain\Rating\RateableId;
use BarAssistant\Domain\Rating\RateableType;
use BarAssistant\Domain\Rating\RatingRepository;
use BarAssistant\Domain\User\UserId;

final class InMemoryRatingRepository implements RatingRepository
{
    /**
     * @param array<int, Rating> $ratings
     */
    public function __construct(
        private array $ratings = [],
    ) {
    }

    public function findUserRating(RateableId $rateableId, RateableType $type, UserId $userId): ?Rating
    {
        foreach ($this->ratings as $rating) {
            if (
                $rating->getRateableId()->equals($rateableId)
                && $rating->getType() === $type
                && $rating->getUserId()->equals($userId)
            ) {
                return $rating;
            }
        }

        return null;
    }

    public function save(Rating $rating): Rating
    {
        if ($rating->isTransient()) {
            $id = count($this->ratings) + 1;
            $rating->setId(new RatingId($id));
        }

        $this->ratings[$rating->getId()->value] = $rating;

        return $rating;
    }

    public function delete(RatingId $id): void
    {
        unset($this->ratings[$id->value]);
    }

    /**
     * @return Rating[]
     */
    public function all(): array
    {
        return array_values($this->ratings);
    }
}
