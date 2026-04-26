<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class CopyCocktail
{
    /**
     * @param int[] $images
     */
    public function __construct(
        public int $barId,
        public int $cocktailId,
        public int $userId,
        public array $images = [],
    ) {
    }
}
