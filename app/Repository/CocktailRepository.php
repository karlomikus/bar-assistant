<?php

declare(strict_types=1);

namespace Kami\Cocktail\Repository;

use Illuminate\Support\Collection;
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
    public function getCocktailsByIngredients(array $ingredientIds, ?int $limit = null, bool $useParentIngredientAsSubstitute = false): Collection
    {
        $query = $this->db->table('cocktails AS c')
            ->select('c.id')
            ->join('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'c.id')
            ->leftJoin('cocktail_ingredient_substitutes AS cis', 'cis.cocktail_ingredient_id', '=', 'ci.id')
            ->where('optional', false);

        if ($useParentIngredientAsSubstitute) {
            $query->join('ingredients AS i', function ($join) {
                $join->on('i.id', '=', 'ci.ingredient_id');
            })
            ->leftJoin('ingredients AS pi', function ($join) { // Faster than OR join
                $join->on('pi.parent_ingredient_id', '=', 'ci.ingredient_id');
            })
            ->whereIn('i.id', $ingredientIds)
            ->orWhereIn('i.parent_ingredient_id', $ingredientIds)
            ->orWhereIn('pi.id', $ingredientIds)
            ->orWhereIn('pi.parent_ingredient_id', $ingredientIds);
        } else {
            $query->join('ingredients AS i', 'i.id', '=', 'ci.ingredient_id')
            ->whereIn('i.id', $ingredientIds);
        }

        $query->orWhereIn('cis.ingredient_id', $ingredientIds)
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
                ->with('ingredients')
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
