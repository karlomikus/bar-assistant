<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Cocktail\ShelfCocktailQueryRepository;

final class InMemoryShelfCocktailQueryRepository implements ShelfCocktailQueryRepository
{
    /**
     * @var array<int, array{barId: int, requiredIngredientIds: array<int, int>}>
     */
    private array $cocktails = [];

    /**
     * @param array<int, int> $requiredIngredientIds
     */
    public function addCocktail(int $cocktailId, int $barId, array $requiredIngredientIds): void
    {
        $this->cocktails[$cocktailId] = [
            'barId' => $barId,
            'requiredIngredientIds' => $requiredIngredientIds,
        ];
    }

    public function findMakeableCocktailIds(BarId $barId, array $ingredientIds, ?int $limit = null): array
    {
        $shelfIngredientIds = array_map(
            static fn (IngredientId $ingredientId): int => $ingredientId->value,
            $ingredientIds
        );

        $cocktailIds = [];
        foreach ($this->cocktails as $cocktailId => $cocktail) {
            if ($cocktail['barId'] !== $barId->value) {
                continue;
            }

            if (array_diff($cocktail['requiredIngredientIds'], $shelfIngredientIds) !== []) {
                continue;
            }

            $cocktailIds[] = new CocktailId($cocktailId);
        }

        if ($limit !== null) {
            $cocktailIds = array_slice($cocktailIds, 0, $limit);
        }

        return $cocktailIds;
    }
}
