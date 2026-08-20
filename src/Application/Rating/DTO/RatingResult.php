<?php

declare(strict_types=1);

namespace BarAssistant\Application\Rating\DTO;

final readonly class RatingResult
{
    public function __construct(
        public int $id,
        public int $cocktailId,
        public int $barMembershipId,
        public float $value,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
