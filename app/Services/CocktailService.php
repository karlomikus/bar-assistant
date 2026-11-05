<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Illuminate\Support\Collection;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Database\DatabaseManager;
use BarAssistant\Application\Cocktail\ShelfCocktailService;
use BarAssistant\Application\Cocktail\DTO\FindShelfCocktails;
use BarAssistant\Application\Ingredient\IngredientService as IngredientApplicationService;

final readonly class CocktailService
{
    public function __construct(
        private DatabaseManager $db,
        private ShelfCocktailService $shelfCocktailService,
        private IngredientApplicationService $ingredientApplicationService,
    ) {
    }

    /**
     * Expand a list of ingredient IDs with their bar-scoped descendants.
     *
     * @param array<int> $ingredientIds
     * @return array<int>
     */
    public function expandWithDescendants(array $ingredientIds, int $barId): array
    {
        return $this->ingredientApplicationService->expandWithDescendants($ingredientIds, $barId);
    }

    /**
     * Return all cocktails that user can create with
     * ingredients in his shelf
     *
     * @param array<int> $ingredientIds
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function getCocktailsByIngredients(array $ingredientIds, int $barId, ?int $limit = null, bool $matchComplexIngredients = true): Collection
    {
        $cocktailIds = $this->shelfCocktailService->findShelfCocktails(new FindShelfCocktails(
            barId: $barId,
            ingredientIds: $ingredientIds,
            matchComplexIngredients: $matchComplexIngredients,
            limit: $limit,
        ));

        return collect($cocktailIds);
    }

    /**
     * Get similar cocktails, prefers cocktails with same base ingredient
     *
     * @return Collection<int, Cocktail>
     */
    public function getSimilarCocktails(Cocktail $cocktailReference, int $limitTotal = 5): Collection
    {
        $ingredients = $cocktailReference->ingredients->filter(fn ($ci) => $ci->optional === false)->pluck('ingredient_id');

        $relatedCocktails = collect();
        while ($ingredients->count() > 0) {
            $ingredients->pop();
            $possibleRelatedCocktails = $this->db->table('cocktails')
                ->select('cocktails.id')
                ->where('cocktails.id', '<>', $cocktailReference->id)
                ->where('bar_id', $cocktailReference->bar_id)
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

        return $relatedCocktails->pluck('id');
    }

    /**
     * @return Collection<array-key, mixed>
     */
    public function getTopRatedCocktails(int $barId, int $limit = 10): Collection
    {
        return $this->db->table('ratings')
            ->select('rateable_id AS id', 'cocktails.name as name', 'cocktails.slug as slug', $this->db->raw('AVG(rating) AS avg_rating'), $this->db->raw('COUNT(*) AS votes'))
            ->join('cocktails', 'cocktails.id', '=', 'ratings.rateable_id')
            ->where('rateable_type', Cocktail::class)
            ->where('cocktails.bar_id', $barId)
            ->groupBy('rateable_id')
            ->orderBy('avg_rating', 'desc')
            ->orderBy('votes', 'desc')
            ->limit($limit)
            ->get();
    }
}
