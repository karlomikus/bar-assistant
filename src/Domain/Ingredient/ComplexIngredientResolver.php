<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

interface ComplexIngredientResolver
{
    /**
     * Find IDs of ingredients that can be fully composed as complex
     * ingredients from the given ingredient IDs.
     *
     * @param array<int, IngredientId> $ingredientIds
     * @return array<int, IngredientId>
     */
    public function findMakeableMainIngredientIds(array $ingredientIds): array;
}
