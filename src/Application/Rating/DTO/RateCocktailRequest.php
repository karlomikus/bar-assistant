<?php

declare(strict_types=1);

namespace BarAssistant\Application\Rating\DTO;

final readonly class RateCocktailRequest
{
    public function __construct(
        public int $userId,
        public int $cocktailId,
        public int $value,
    ) {
    }
}
