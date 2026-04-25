<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI\DTO;

final readonly class CocktailImageRequest
{
    public function __construct(
        public string $cocktailName,
        public string $cocktailRecipe,
        public ?string $glassName = null,
        public ?string $garnish = null,
        public ?string $style = null,
    ) {
    }
}
