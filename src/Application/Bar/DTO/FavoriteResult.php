<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

final readonly class FavoriteResult
{
    public function __construct(
        public int $cocktailId,
        public bool $isFavorited,
        public ?string $favoritedAt = null,
    ) {
    }
}
