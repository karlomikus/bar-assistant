<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class CreateCocktail
{
    public function __construct(
        public int $barId,
        public string $name,
        public string $instructions,
        public int $userId,
    ) {
    }
}
