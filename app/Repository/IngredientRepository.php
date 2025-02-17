<?php

declare(strict_types=1);

namespace Kami\Cocktail\Repository;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Database\DatabaseManager;

readonly class IngredientRepository
{
    public function __construct(private DatabaseManager $db)
    {
    }

    /**
     * @return Collection<int, mixed>
     */
    public function getMainIngredientsOfCocktails(int $barId): Collection
    {
        return $this->db->table('cocktail_ingredients')
            ->selectRaw('cocktail_ingredients.ingredient_id, COUNT(cocktail_ingredients.cocktail_id) AS cocktails')
            ->join('cocktails', 'cocktails.id', '=', 'cocktail_ingredients.cocktail_id')
            ->where('sort', 1)
            ->where('cocktails.bar_id', $barId)
            ->groupBy('cocktail_id')
            ->orderBy('cocktails.name', 'desc')
            ->get();
    }

    /**
     * Collect all descendants of the given ingredients and manually set the relation
     * Used for optimizing the query when querying for ingredients
     *
     * @param Collection<int, Ingredient> $ingredients
     * @return Collection<int, mixed>
     */
    public function loadHierarchy(Collection $ingredients): Collection
    {
        $onlyRootIngredients = $ingredients->filter(fn ($ingredient) => $ingredient->isRoot());

        // Collect all descendants without root ingredients
        $ingredientDescendants = Ingredient::select('ingredients.id as _root_id', 'descendant.*')
            ->join('ingredients AS descendant', 'descendant.materialized_path', 'LIKE', DB::raw("ingredients.id || '/%'"))
            ->whereNull('ingredients.parent_ingredient_id')
            ->whereIn('ingredients.id', $onlyRootIngredients->pluck('id'))
            ->get();

        $ingredientAncestors = Ingredient::select('ingredients.id as leaf_id', 'ancestor.*')
            ->join('ingredients AS ancestor', DB::raw("instr('/' || ingredients.materialized_path || '/', '/' || ancestor.id || '/')"), '>', DB::raw('0'))
            ->whereIn('ingredients.id', $ingredients->pluck('id'))
            ->whereNotNull('ingredients.materialized_path')
            ->orderBy('leaf_id')
            ->orderBy('ancestor.id')
            ->get();

        // Manually set eloquent relations
        $ingredients->map(function ($ingredient) use ($ingredientDescendants, $ingredientAncestors) {
            $descendants = $ingredientDescendants->filter(function ($possibleDescendant) use ($ingredient) {
                return $possibleDescendant->isDescendantOf($ingredient);
            });
            $ingredient->setDescendants($descendants);

            $ancestors = $ingredientAncestors->filter(function ($possibleAncestor) use ($ingredient) {
                return $possibleAncestor['leaf_id'] === $ingredient->id;
            })->sort(function ($a, $b) use ($ingredient) {
                // Sort by position in materialized path
                $path = $ingredient->getMaterializedPath()->toArray();
                $posA = (int) array_search($a['id'], $path);
                $posB = (int) array_search($b['id'], $path);

                return $posA - $posB;
            });
            $ingredient->setAncestors($ancestors);

            return $ingredient;
        });

        return $ingredients;
    }

    /**
     * @param array<int> $ingredientIds
     * @return Collection<array-key, Ingredient>
     */
    public function getDescendants(array $ingredientIds, int $limit = 50): Collection
    {
        return Ingredient::select('ingredients.id AS _root_id', 'descendant.*')
            ->join('ingredients AS descendant', DB::raw("('/' || descendant.materialized_path || '/')"), 'LIKE', DB::raw("'%/' || ingredients.id || '/%'"))
            ->whereIn('ingredients.id', $ingredientIds)
            ->limit($limit)
            ->orderBy('ingredients.name')
            ->get();
    }

    /**
     * @param array<int> $ingredientIds
     * @return array<int, object>
     */
    public function getIngredientsForPossibleCocktails(int $barId, array $ingredientIds): array
    {
        $placeholders = implode(',', array_map(fn ($id) => (int) $id, $ingredientIds));

        $rawQuery = "SELECT
            pi.ingredient_id as id,
            pi.ingredient_slug as slug,
            pi.ingredient_name as name,
            pi.potential_cocktails
        FROM
            (
                SELECT
                    mi.ingredient_id,
                    mi.ingredient_slug,
                    mi.ingredient_name,
                    COUNT(DISTINCT c.id) AS potential_cocktails
                FROM
                    (
                        -- Step 1: Ingredients the user doesn't have
                        SELECT
                            i.id AS ingredient_id,
                            i.slug AS ingredient_slug,
                            i.name AS ingredient_name
                        FROM
                            ingredients i
                        WHERE
                            i.id NOT IN (" . $placeholders . ")
                            and bar_id = :barId

                        EXCEPT

                        -- Step 2: Complex ingredients, user has ingredients in their shelf to make them
                        SELECT
                            ci.main_ingredient_id AS ingredient_id,
                            i.slug AS ingredient_slug,
                            i.name AS ingredient_name
                        FROM
                            complex_ingredients ci
                            JOIN ingredients i ON ci.main_ingredient_id = i.id
                        WHERE
                            ci.main_ingredient_id NOT IN (" . $placeholders . ")
                            AND ci.ingredient_id IN (" . $placeholders . ")
                            AND i.bar_id = :barId
                    ) mi
                    JOIN cocktail_ingredients ci ON mi.ingredient_id = ci.ingredient_id
                    JOIN cocktails c ON ci.cocktail_id = c.id
                GROUP BY
                    mi.ingredient_id,
                    mi.ingredient_slug,
                    mi.ingredient_name
            ) pi
        ORDER BY
            pi.potential_cocktails DESC
        LIMIT 10";

        return DB::select($rawQuery, ['barId' => $barId]);
    }
}
