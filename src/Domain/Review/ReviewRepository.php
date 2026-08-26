<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Review;

use BarAssistant\Domain\Bar\MemberId;

interface ReviewRepository
{
    public function findById(ReviewId $id): ?Review;

    public function save(Review $review): Review;

    public function delete(ReviewId $id): void;

    public function existsForMemberAndCocktail(MemberId $memberId, int $cocktailId): bool;
}
