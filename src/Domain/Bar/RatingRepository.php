<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use BarAssistant\Domain\Cocktail\CocktailId;

interface RatingRepository
{
    public function findById(RatingId $id): ?Rating;

    public function findByCocktailAndMember(CocktailId $cocktailId, MemberId $memberId): ?Rating;

    /**
     * @return Rating[]
     */
    public function findByMember(MemberId $memberId): array;

    /**
     * @return Rating[]
     */
    public function findByCocktail(CocktailId $cocktailId): array;

    public function save(Rating $rating): Rating;

    public function delete(RatingId $id): void;

    /**
     * Get cocktail IDs that have been rated low by a member
     *
     * @return CocktailId[]
     */
    public function findLowRatedCocktailIds(MemberId $memberId, int $maxValue = 2): array;
}
