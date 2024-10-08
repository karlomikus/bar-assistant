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
    public function getCocktailsByIngredients(array $ingredientIds, ?int $limit = null, bool $useParentIngredientAsSubstitute = false, bool $matchComplexIngredients = true): Collection
    {
        // Resolve complex ingredients
        // Basically, goes through all ingredients to match ($ingredientIds) and check if they can create complex ingredients
        // If they can, that ingredient is added to the list of ingredients to match
        if ($matchComplexIngredients) {
            $rawQuery = "WITH RECURSIVE IngredientChain AS (
                    SELECT id AS matched_ingredient
                    FROM ingredients
                    WHERE id IN (" . implode(',', $ingredientIds) . ")
                    UNION
                    SELECT ci.main_ingredient_id AS matched_ingredient
                    FROM complex_ingredients ci
                    INNER JOIN IngredientChain ic ON ci.ingredient_id = ic.matched_ingredient
                )
                SELECT DISTINCT matched_ingredient
                FROM IngredientChain;";

            $additionalIngredients = collect(DB::select($rawQuery))->pluck('matched_ingredient');
            $ingredientIds = array_merge($ingredientIds, $additionalIngredients->toArray());
            $ingredientIds = array_unique($ingredientIds);
        }

        $query = $this->db->table('cocktails AS c')
            ->select('c.id')
            ->join('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'c.id')
            ->join('ingredients AS i', 'i.id', '=', 'ci.ingredient_id')
            ->leftJoin('cocktail_ingredient_substitutes AS cis', 'cis.cocktail_ingredient_id', '=', 'ci.id')
            ->where('optional', false)
            ->where(function ($query) use ($ingredientIds, $useParentIngredientAsSubstitute) {
                $query->whereIn('i.id', $ingredientIds)->orWhereIn('cis.ingredient_id', $ingredientIds);

                if ($useParentIngredientAsSubstitute) {
                    $query->orWhereIn('i.id', function ($parentSubquery) use ($ingredientIds) {
                        $parentSubquery
                            ->select('parent_ingredient_id')
                            ->from('ingredients')
                            ->whereIn('id', $ingredientIds)
                            ->whereNotNull('parent_ingredient_id');
                    });
                }
            })
            ->groupBy('c.id')
            ->havingRaw('COUNT(*) >= (SELECT COUNT(*) FROM cocktail_ingredients WHERE cocktail_id = c.id AND optional = false)');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->pluck('id');
    }

    /**
     * Match cocktails ingredients to users shelf ingredients
     * Does not include substitutes
     *
     * @param int $cocktailId
     * @param int $userId
     * @return array<int>
     */
    public function matchAvailableShelfIngredients(int $cocktailId, int $userId): array
    {
        return $this->db->table('ingredients AS i')
            ->select('i.id')
            ->leftJoin('user_ingredients AS ui', 'ui.ingredient_id', '=', 'i.id')
            ->where('ui.user_id', $userId)
            ->whereRaw('i.id IN (SELECT ingredient_id FROM cocktail_ingredients ci WHERE ci.cocktail_id = ?)', [$cocktailId])
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get cocktail ids with number of missing user ingredients
     *
     * @param int $userId
     * @param string $direction
     * @return Collection<int, mixed>
     */
    public function getCocktailsWithMissingIngredientsCount(int $userId, string $direction = 'desc'): Collection
    {
        return $this->db->table('cocktails AS c')
            ->selectRaw('c.id, COUNT(ci.ingredient_id) - COUNT(ui.ingredient_id) AS missing_ingredients')
            ->leftJoin('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'c.id')
            ->leftJoin('user_ingredients AS ui', function ($query) use ($userId) {
                $query->on('ui.ingredient_id', '=', 'ci.ingredient_id')->where('ui.user_id', $userId);
            })
            ->groupBy('c.id')
            ->orderBy('missing_ingredients', $direction)
            ->having('missing_ingredients', '>', 0)
            ->get();
    }

    /**
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
