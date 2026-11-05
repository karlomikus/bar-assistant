<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Application\Cocktail\DTO\FindShelfCocktails;
use BarAssistant\Domain\Ingredient\ComplexIngredientResolver;
use BarAssistant\Domain\Cocktail\ShelfCocktailQueryRepository;

final readonly class ShelfCocktailService
{
    public function __construct(
        private ShelfCocktailQueryRepository $shelfCocktailQueryRepository,
        private ComplexIngredientResolver $complexIngredientResolver,
    ) {
    }

    /**
     * Find all cocktails that can be made with the given ingredients.
     *
     * When matching complex ingredients is enabled, ingredients that can be
     * fully composed from the shelf are added to the match before querying.
     *
     * @return array<int, int>
     */
    public function findShelfCocktails(FindShelfCocktails $request): array
    {
        if (count($request->ingredientIds) === 0) {
            return [];
        }

        $ingredientIds = array_map(
            static fn (int $ingredientId): IngredientId => new IngredientId($ingredientId),
            array_values(array_unique($request->ingredientIds))
        );

        if ($request->matchComplexIngredients) {
            $ingredientIds = $this->mergeUnique(
                $ingredientIds,
                $this->complexIngredientResolver->findMakeableMainIngredientIds($ingredientIds)
            );
        }

        return array_map(
            static fn (CocktailId $cocktailId): int => $cocktailId->value,
            $this->shelfCocktailQueryRepository->findMakeableCocktailIds(
                new BarId($request->barId),
                $ingredientIds,
                $request->limit,
            )
        );
    }

    /**
     * @param array<int, IngredientId> $ingredientIds
     * @param array<int, IngredientId> $additionalIngredientIds
     * @return array<int, IngredientId>
     */
    private function mergeUnique(array $ingredientIds, array $additionalIngredientIds): array
    {
        $seen = [];
        foreach ($ingredientIds as $ingredientId) {
            $seen[$ingredientId->value] = true;
        }

        foreach ($additionalIngredientIds as $additionalIngredientId) {
            if (!isset($seen[$additionalIngredientId->value])) {
                $seen[$additionalIngredientId->value] = true;
                $ingredientIds[] = $additionalIngredientId;
            }
        }

        return $ingredientIds;
    }
}
