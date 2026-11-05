<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use Illuminate\Database\DatabaseManager;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Cocktail\ShelfCocktailQueryRepository;

final class EloquentShelfCocktailQueryRepository implements ShelfCocktailQueryRepository
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function findMakeableCocktailIds(BarId $barId, array $ingredientIds, ?int $limit = null): array
    {
        if (count($ingredientIds) === 0) {
            return [];
        }

        $ids = array_map(static fn (IngredientId $ingredientId): int => $ingredientId->value, $ingredientIds);
        $inShelfIngredients = $this->createInClause(count($ids));
        $bindings = [...$ids, ...$ids, ...$ids];

        // This query should handle the following cases:
        // Correctly count one match when either the main ingredient OR any of its substitutes match
        // If an ingredient can be matched either directly or through a substitute, it should only count once
        // Match any of descendant ingredients as possible substitute
        $matchingIngredientsCase = <<<SQL
            COUNT(DISTINCT CASE
                WHEN cocktail_ingredients.ingredient_id IN {$inShelfIngredients} THEN cocktail_ingredients.ingredient_id
                WHEN cocktail_ingredient_substitutes.ingredient_id IN {$inShelfIngredients} THEN cocktail_ingredients.ingredient_id
                WHEN cocktail_ingredients.is_specified IS FALSE AND EXISTS (
                    SELECT
                        1
                    FROM
                        ingredients
                    WHERE
                        (
                            ingredients.parent_ingredient_id = cocktail_ingredients.ingredient_id
                            OR ingredients.materialized_path LIKE (
                                SELECT COALESCE(ancestor.materialized_path, '') || ancestor.id || '/%'
                                FROM ingredients AS ancestor
                                WHERE ancestor.id = cocktail_ingredients.ingredient_id
                            )
                        )
                        AND ingredients.id IN {$inShelfIngredients}
                ) THEN cocktail_ingredients.ingredient_id
                ELSE NULL
            END) as matching_ingredients
            SQL;

        $cocktailIds = $this->db->table('cocktails')
            ->select('cocktails.id')
            ->selectRaw($matchingIngredientsCase, $bindings)
            ->join('cocktail_ingredients', 'cocktails.id', '=', 'cocktail_ingredients.cocktail_id')
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->leftJoin('cocktail_ingredient_substitutes', 'cocktail_ingredient_substitutes.cocktail_ingredient_id', '=', 'cocktail_ingredients.id')
            ->joinSub(
                $this->db->table('cocktail_ingredients')
                    ->select('cocktail_id')
                    ->selectRaw('COUNT(*) as required_ingredients')
                    ->where('optional', false)
                    ->groupBy('cocktail_id'),
                'required',
                'required.cocktail_id',
                '=',
                'cocktails.id'
            )
            ->where('cocktail_ingredients.optional', false)
            ->where('cocktails.bar_id', $barId->value) // This uses index on table to skip SCAN on whole cocktails table
            ->groupBy('cocktails.id', 'required.required_ingredients')
            ->havingRaw('matching_ingredients >= required.required_ingredients');

        if ($limit !== null) {
            $cocktailIds->limit($limit);
        }

        return array_map(
            static fn ($cocktailId): CocktailId => new CocktailId((int) $cocktailId),
            $cocktailIds->pluck('id')->toArray()
        );
    }

    private function createInClause(int $parameterCount): string
    {
        return '(' . rtrim(str_repeat('?,', $parameterCount), ',') . ')';
    }
}
