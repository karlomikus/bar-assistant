<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class CocktailResult
{
    public function __construct(
        public int $id,
        public string $slug,
    ) {
    }
}
