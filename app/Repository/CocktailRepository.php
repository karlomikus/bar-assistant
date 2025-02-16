<?php

declare(strict_types=1);

namespace Kami\Cocktail\Repository;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Database\DatabaseManager;

readonly class CocktailRepository
{
    public function __construct(private DatabaseManager $db)
    {
    }

    /**
     * Return all cocktails that user can create with
     * ingredients in his shelf
     *
     * @param array<int> $ingredientIds
     * @return \Illuminate\Support\Collection<string, mixed>
     */
    public function getCocktailsByIngredients(array $ingredientIds, ?int $limit = null, bool $matchComplexIngredients = true): Collection
    {
        if (count($ingredientIds) === 0) {
            return collect();
        }

        // Resolve complex ingredients
        // Basically, goes through all ingredients to match ($ingredientIds) and check if they can create complex ingredients
        // If they can, that ingredient is added to the list of ingredients to match
        if ($matchComplexIngredients) {
            $placeholders = str_repeat('?,', count($ingredientIds) - 1) . '?';
            $rawQuery = "WITH RECURSIVE IngredientChain AS (
                    SELECT id AS matched_ingredient
                    FROM ingredients
                    WHERE id IN (" . $placeholders . ")
                    UNION
                    SELECT ci.main_ingredient_id AS matched_ingredient
                    FROM complex_ingredients ci
                    INNER JOIN IngredientChain ic ON ci.ingredient_id = ic.matched_ingredient
                )
                SELECT DISTINCT matched_ingredient
                FROM IngredientChain;";

            $additionalIngredients = collect(DB::select($rawQuery, $ingredientIds))->pluck('matched_ingredient');
            $ingredientIds = array_merge($ingredientIds, $additionalIngredients->toArray());
            $ingredientIds = array_unique($ingredientIds);
        }

        // This query should handle the following cases:
        // Correctly count one match when either the main ingredient OR any of its substitutes match
        // If an ingredient can be matched either directly or through a substitute, it should only count once
        // Match any of descendant ingredients as possible substitute
        $query = $this->db->table('cocktails')
            ->select('cocktails.id')
            ->selectRaw(
                'COUNT(DISTINCT CASE
                    WHEN ingredients.id IN (' . str_repeat('?,', count($ingredientIds) - 1) . '?) THEN ingredients.id
                    WHEN cocktail_ingredient_substitutes.ingredient_id IN (' . str_repeat('?,', count($ingredientIds) - 1) . '?) THEN ingredients.id
                    WHEN ingredients.id IN (
                        SELECT parent_ingredient_id 
                        FROM ingredients 
                        WHERE id IN (' . str_repeat('?,', count($ingredientIds) - 1) . '?)
                        AND parent_ingredient_id IS NOT NULL
                    ) THEN ingredients.id
                    WHEN EXISTS (
                        SELECT
                            1
                        FROM
                            ingredients
                        WHERE
                            materialized_path LIKE cocktail_ingredients.ingredient_id || \'/%\'
                            AND id IN (' . str_repeat('?,', count($ingredientIds) - 1) . '?)
                    ) THEN ingredients.id
                    ELSE NULL
                END) as matching_ingredients',
                [...$ingredientIds, ...$ingredientIds, ...$ingredientIds, ...$ingredientIds]
            )
            ->join('cocktail_ingredients', 'cocktails.id', '=', 'cocktail_ingredients.cocktail_id')
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->leftJoin('cocktail_ingredient_substitutes', 'cocktail_ingredient_substitutes.cocktail_ingredient_id', '=', 'cocktail_ingredients.id')
            ->where('cocktail_ingredients.optional', false)
            ->groupBy('cocktails.id')
            ->havingRaw('matching_ingredients >= (
                SELECT COUNT(*)
                FROM cocktail_ingredients ci2
                WHERE ci2.cocktail_id = cocktails.id
                AND ci2.optional = false
            )');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->pluck('id');
    }

    /**
     * Get similar cocktails, prefers cocktails with same base ingredient
     *
     * @return Collection<int, Cocktail>
     */
    public function getSimilarCocktails(Cocktail $cocktailReference, int $limitTotal = 5): Collection
    {
        $ingredients = $cocktailReference->ingredients->filter(fn ($ci) => $ci->optional === false)->pluck('ingredient_id');

        $relatedCocktails = \collect();
        while ($ingredients->count() > 0) {
            $ingredients->pop();
            $possibleRelatedCocktails = Cocktail::where('cocktails.id', '<>', $cocktailReference->id)
                ->where('bar_id', $cocktailReference->bar_id)
                ->with('ingredients.ingredient', 'images', 'tags', 'ratings')
                ->whereIn('cocktails.id', function ($query) use ($ingredients) {
                    $query->select('ci.cocktail_id')
                        ->from('cocktail_ingredients AS ci')
                        ->whereIn('ci.ingredient_id', $ingredients)
                        ->where('optional', false)
                        ->groupBy('ci.cocktail_id')
                        ->havingRaw('COUNT(DISTINCT ci.ingredient_id) = ?', [$ingredients->count()]);
                })
                ->get();

            $relatedCocktails = $relatedCocktails->merge($possibleRelatedCocktails)->unique('id');
            if ($relatedCocktails->count() > $limitTotal) {
                $relatedCocktails = $relatedCocktails->take($limitTotal);
                break;
            }
        }

        return $relatedCocktails;
    }
}
