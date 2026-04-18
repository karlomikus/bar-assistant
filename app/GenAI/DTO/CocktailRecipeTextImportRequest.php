<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI\DTO;

final readonly class CocktailRecipeTextImportRequest
{
    /**
     * @param string[] $allowedMethods
     */
    public function __construct(
        public string $textRecipe,
        public array $allowedMethods = [],
    ) {
    }
}
