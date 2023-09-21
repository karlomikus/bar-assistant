<?php

declare(strict_types=1);

namespace Kami\Cocktail\DataObjects\Ingredient;

class Ingredient
{
    /**
     * @param array<int> $images
     */
    public function __construct(
        public readonly int $barId,
        public readonly string $name,
        public readonly int $userId,
        public readonly ?int $ingredientCategoryId = null,
        public readonly float $strength = 0.0,
        public readonly ?string $description = null,
        public readonly ?string $origin = null,
        public readonly ?string $color = null,
        public readonly ?int $parentIngredientId = null,
        public readonly array $images = []
    ) {
    }
}
