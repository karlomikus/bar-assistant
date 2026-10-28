<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI\DTO;

final readonly class IngredientImageRequest
{
    public function __construct(
        public string $ingredientName,
        public ?string $category = null,
        public ?string $description = null,
        public ?string $parentIngredientName = null,
        public ?string $style = null,
    ) {
    }
}
