<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Rating;

use BarAssistant\Domain\Bar\MemberId;

interface RatingRepository
{
    public function findMemberRating(RateableId $rateableId, RateableType $type, MemberId $memberId): ?Rating;

    public function save(Rating $rating): Rating;

    public function delete(RatingId $id): void;
}
