<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\IngredientId;

interface ShelfCocktailQueryRepository
{
    /**
     * Find IDs of cocktails that can be fully made with the given ingredients.
     *
     * A cocktail matches when every non-optional ingredient is covered, either
     * directly, through a substitute, or through one of its descendants.
     *
     * @param array<int, IngredientId> $ingredientIds
     * @return array<int, CocktailId>
     */
    public function findMakeableCocktailIds(BarId $barId, array $ingredientIds, ?int $limit = null): array;
}
