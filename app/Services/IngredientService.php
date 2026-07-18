<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
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
    public function getIngredientsOrderedByUnlockedCocktails(int $barId, array $existingIngredients): array
    {
        $ingredientIds = array_unique(array_merge(
            $existingIngredients,
            $this->resolveComplexIngredients($existingIngredients),
        ));
        $availableIngredientIds = array_fill_keys($ingredientIds, true);
        $availableIngredientAncestorIds = [];

        if ($ingredientIds !== []) {
            $availableIngredients = DB::table('ingredients')
                ->where('bar_id', $barId)
                ->whereIn('id', $ingredientIds)
                ->select('materialized_path')
                ->get();

            foreach ($availableIngredients as $availableIngredient) {
                if (!is_string($availableIngredient->materialized_path) || $availableIngredient->materialized_path === '') {
                    continue;
                }

                foreach (explode('/', trim($availableIngredient->materialized_path, '/')) as $ancestorId) {
                    if ($ancestorId === '') {
                        continue;
                    }

                    $availableIngredientAncestorIds[(int) $ancestorId] = true;
                }
            }
        }

        $barCocktails = Cocktail::where('bar_id', $barId)->with('ingredients')->get();

        // Count every missing required ingredient for each cocktail, not only
        // single-missing unlocks. Empty shelf then ranks by cocktail frequency.
        $potentialCocktails = [];
        foreach ($barCocktails as $barCocktail) {
            $missingIngredientIds = [];

            foreach ($barCocktail->ingredients as $cocktailIngredient) {
                if ($cocktailIngredient->optional
                    || isset($availableIngredientIds[$cocktailIngredient->ingredient_id])
                    || ($cocktailIngredient->is_specified === false && isset($availableIngredientAncestorIds[$cocktailIngredient->ingredient_id]))) {
                    continue;
                }

                $missingIngredientIds[$cocktailIngredient->ingredient_id] = true;
            }

            foreach (array_keys($missingIngredientIds) as $missingIngredientId) {
                $potentialCocktails[$missingIngredientId] = ($potentialCocktails[$missingIngredientId] ?? 0) + 1;
            }
        }

        if ($potentialCocktails === []) {
            return [];
        }

        $ingredients = DB::table('ingredients')
            ->where('bar_id', $barId)
            ->whereIn('id', array_keys($potentialCocktails))
            ->get();

        return $ingredients
            ->map(fn (object $ingredient): object => (object) [
                'id' => $ingredient->id,
                'slug' => $ingredient->slug,
                'name' => $ingredient->name,
                'potential_cocktails' => $potentialCocktails[$ingredient->id] ?? 0,
            ])
            ->sort(function (object $left, object $right): int {
                if ($left->potential_cocktails === $right->potential_cocktails) {
                    return strcmp($left->name, $right->name);
                }

                return $right->potential_cocktails <=> $left->potential_cocktails;
            })
            ->values()
            ->all();
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
