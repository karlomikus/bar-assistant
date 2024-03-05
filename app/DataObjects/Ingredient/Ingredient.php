<?php

declare(strict_types=1);

namespace Kami\Cocktail\DataObjects\Ingredient;

readonly class Ingredient
{
    /**
     * @param array<int> $images
     */
    public function __construct(
        public int $barId,
        public string $name,
        public int $userId,
        public ?int $ingredientCategoryId = null,
        public float $strength = 0.0,
        public ?string $description = null,
        public ?string $origin = null,
        public ?string $color = null,
        public ?int $parentIngredientId = null,
        public array $images = []
    ) {
    }
}
