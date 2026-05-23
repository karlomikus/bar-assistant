<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\DatabaseManager;

final readonly class IngredientService
{
    public function __construct(
        private LogManager $log,
        private DatabaseManager $db,
    ) {
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
     * @param array<int> $existingIngredients
     * @return array<int, object>
     */
    public function getIngredientsForPossibleCocktails(int $barId, array $existingIngredients): array
    {
        $placeholders = implode(',', array_map(fn ($id) => (int) $id, $existingIngredients));

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

    public function rebuildMaterializedPath(int $barId): void
    {
        $startTime = microtime(true);
        $ingredients = DB::table('ingredients')
            ->where('bar_id', $barId)
            ->select('id', 'parent_ingredient_id')
            ->get()
            ->keyBy('id');

        $paths = [];
        foreach ($ingredients as $ingredient) {
            if ($ingredient->parent_ingredient_id === null) {
                $paths[$ingredient->id] = null;
                continue;
            }

            $path = '';
            $current = $ingredient;
            while ($current && $current->parent_ingredient_id !== null) {
                $path = $current->parent_ingredient_id . '/' . $path;
                $current = $ingredients->get($current->parent_ingredient_id);
            }
            $paths[$ingredient->id] = $path;
        }

        $cases = [];
        $bindings = [];
        $ids = [];

        foreach ($paths as $id => $path) {
            $cases[] = "WHEN ? THEN ?";
            $bindings[] = $id;
            $bindings[] = $path;
            $ids[] = $id;
        }

        if (empty($ids)) {
            return;
        }

        $ids = implode(',', $ids);
        $cases = implode(' ', $cases);

        DB::update("UPDATE ingredients SET materialized_path = CASE id {$cases} END WHERE id IN ({$ids})", $bindings);

        $endTime = microtime(true);
        $this->log->info('[INGREDIENT_SERVICE] Rebuilt materialized path for ingredients for bar ' . $barId . ' in ' . round($endTime - $startTime, 6) . ' seconds.');
    }

    /**
     * Resolve complex ingredients that can be made with the given ingredient IDs.
     *
     * @param array<int> $ingredientIds
     * @return array<int>
     */
    public function resolveComplexIngredients(array $ingredientIds): array
    {
        return $this->db->table('complex_ingredients AS ci')
            ->distinct()
            ->select('ci.main_ingredient_id')
            ->join('ingredients AS i_main', 'ci.main_ingredient_id', '=', 'i_main.id')
            ->whereIn('ci.id', function ($query) use ($ingredientIds) {
                $query->select('ci_inner.id')
                    ->from('complex_ingredients AS ci_inner')
                    ->whereNotExists(function ($query) use ($ingredientIds) {
                        $query->select('i_ingredient.id')
                            ->from('complex_ingredients AS ci_sub')
                            ->join('ingredients AS i_ingredient', 'ci_sub.ingredient_id', '=', 'i_ingredient.id')
                            ->whereColumn('ci_sub.main_ingredient_id', 'ci_inner.main_ingredient_id')
                            ->whereNotIn('i_ingredient.id', $ingredientIds);
                    });
            })
            ->pluck('main_ingredient_id')
            ->toArray();
    }
}
