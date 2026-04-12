<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Rating;

use BarAssistant\Domain\User\UserId;

interface RatingRepository
{
    public function findUserRating(RateableId $rateableId, RateableType $type, UserId $userId): ?Rating;

    public function save(Rating $rating): Rating;

    public function delete(RatingId $id): void;
}
