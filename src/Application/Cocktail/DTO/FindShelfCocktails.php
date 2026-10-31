<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

/**
 * Request to find cocktails makeable with a set of shelf ingredients.
 */
final readonly class FindShelfCocktails
{
    /**
     * @param array<int, int> $ingredientIds
     */
    public function __construct(
        public int $barId,
        public array $ingredientIds,
        public bool $matchComplexIngredients = true,
        public ?int $limit = null,
    ) {
    }
}
