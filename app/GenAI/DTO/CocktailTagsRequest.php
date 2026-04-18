<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI\DTO;

final readonly class CocktailTagsRequest
{
    /**
     * @param string[] $existingTags
     */
    public function __construct(
        public string $cocktailRecipe,
        public array $existingTags = [],
    ) {
    }
}
