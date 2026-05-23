<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

use DateTimeImmutable;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;

final readonly class CocktailWithDetails
{
    /**
     * @param string[] $tags
     * @param IngredientId[] $ingredientIds
     */
    public function __construct(
        public CocktailId $cocktailId,
        public array $tags,
        public array $ingredientIds,
        public ?DateTimeImmutable $createdAt,
    ) {
    }
}
