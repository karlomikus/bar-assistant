<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Rating\Rating;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Rating\RatingId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Rating\RatingRepository;

final class InMemoryRatingRepository implements RatingRepository
{
    /**
     * @param array<int, Rating> $ratings
     */
    public function __construct(
        private array $ratings = [],
    ) {
    }

    public function findById(RatingId $id): ?Rating
    {
        return $this->ratings[$id->value] ?? null;
    }

    public function findUserRating(CocktailId $cocktailId, MemberId $userId): ?Rating
    {
        foreach ($this->ratings as $rating) {
            if ($rating->getRateableId()->equals($cocktailId) && $rating->getMemberId()->equals($userId)) {
                return $rating;
            }
        }

        return null;
    }

    /**
     * @return Rating[]
     */
    public function findByMember(MemberId $memberId): array
    {
        return array_values(array_filter(
            $this->ratings,
            fn (Rating $rating) => $rating->getMemberId()->equals($memberId),
        ));
    }

    /**
     * @return Rating[]
     */
    public function findByCocktail(CocktailId $cocktailId): array
    {
        return array_values(array_filter(
            $this->ratings,
            fn (Rating $rating) => $rating->getRateableId()->equals($cocktailId),
        ));
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
     * @return CocktailId[]
     */
    public function findLowRatedCocktailIds(MemberId $memberId, int $maxValue = 2): array
    {
        $ids = [];

        foreach ($this->ratings as $rating) {
            if ($rating->getMemberId()->equals($memberId) && $rating->getValue() <= $maxValue) {
                $ids[] = $rating->getRateableId();
            }
        }

        return $ids;
    }
}
