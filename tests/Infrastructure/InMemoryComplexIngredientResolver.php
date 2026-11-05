<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\ComplexIngredientResolver;

final class InMemoryComplexIngredientResolver implements ComplexIngredientResolver
{
    /**
     * @var array<int, array<int, int>> Map of main ingredient ID to its part ingredient IDs
     */
    private array $complexIngredients = [];

    /**
     * @param array<int, int> $partIngredientIds
     */
    public function addComplexIngredient(int $mainIngredientId, array $partIngredientIds): void
    {
        $this->complexIngredients[$mainIngredientId] = $partIngredientIds;
    }

    public function findMakeableMainIngredientIds(array $ingredientIds): array
    {
        $shelfIngredientIds = array_map(
            static fn (IngredientId $ingredientId): int => $ingredientId->value,
            $ingredientIds
        );

        $mainIngredientIds = [];
        foreach ($this->complexIngredients as $mainIngredientId => $partIngredientIds) {
            if (array_diff($partIngredientIds, $shelfIngredientIds) !== []) {
                continue;
            }

            $mainIngredientIds[] = new IngredientId($mainIngredientId);
        }

        return $mainIngredientIds;
    }
}
