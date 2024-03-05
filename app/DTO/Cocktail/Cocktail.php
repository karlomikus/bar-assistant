<?php

declare(strict_types=1);

namespace Kami\Cocktail\DTO\Cocktail;

readonly class Cocktail
{
    /**
     * @param array<?string> $tags
     * @param array<\Kami\Cocktail\DataObjects\Cocktail\Ingredient> $ingredients
     * @param array<int> $images
     * @param array<int> $utensils
     */
    public function __construct(
        public string $name,
        public string $instructions,
        public int $userId,
        public int $barId,
        public ?string $description = null,
        public ?string $source = null,
        public ?string $garnish = null,
        public ?int $glassId = null,
        public ?int $methodId = null,
        public array $tags = [],
        public array $ingredients = [],
        public array $images = [],
        public array $utensils = [],
    ) {
    }
}
