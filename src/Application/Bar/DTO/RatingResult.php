<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

final readonly class RatingResult
{
    public function __construct(
        public int $id,
        public int $cocktailId,
        public int $memberId,
        public int $value,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
