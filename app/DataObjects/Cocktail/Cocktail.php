<?php

declare(strict_types=1);

namespace Kami\Cocktail\DataObjects\Cocktail;

class Cocktail
{
    /**
     * @param array<string> $tags
     * @param array<\Kami\Cocktail\DataObjects\Cocktail\Ingredient> $ingredients
     * @param array<int> $images
     */
    public function __construct(
        public readonly string $name,
        public readonly string $instructions,
        public readonly int $userId,
        public readonly ?string $description = null,
        public readonly ?string $source = null,
        public readonly ?string $garnish = null,
        public readonly ?int $glassId = null,
        public readonly ?int $methodId = null,
        public readonly array $tags = [],
        public readonly array $ingredients = [],
        public readonly array $images = [],
        public readonly array $utensils = [],
    ) {
    }
}
