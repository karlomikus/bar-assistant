<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\BarMembership;

class CocktailRecommendationService
{
    private const TAG_MATCH_WEIGHT = 0.8;
    private const INGREDIENT_MATCH_WEIGHT = 0.5;
    private const BAR_SHELF_INGREDIENT_MATCH_WEIGHT = 0.7;
    private const BAR_SHELF_COMPLETE_MATCH_WEIGHT = 1;
    private const RECENCY_BOOST_WEIGHT = 0.3;

    private const NEGATIVE_RATING_PENALTY = -0.5;

    public function __construct(private readonly CocktailService $cocktailService)
    {
    }

    /**
     * @return \Illuminate\Support\Collection<array-key, Cocktail>
     */
    public function recommend(BarMembership $barMembership, int $limit = 10): Collection
    {
        $barMembership->loadMissing('cocktailFavorites');

        $excludedCocktailIds = $this->getExcludedCocktails($barMembership);

        // Collect all favorite tags
        $memberFavoriteTags = $this->cocktailService->getMemberFavoriteCocktailTags($barMembership->id, null);
        $memberFavoriteTags->map(function ($tag) {
            $tag->weight = $tag->cocktails_count * self::TAG_MATCH_WEIGHT;

            return $tag;
        });

        // Collect negative tags from low-rated cocktails
        $negativeTags = $this->getNegativeTags($barMembership);

        // Collect all favorite ingredients
        $memberFavoriteIngredients = DB::table('cocktail_ingredients')
            ->selectRaw('ingredients.id, COUNT(cocktail_id) * ? AS weight', [self::INGREDIENT_MATCH_WEIGHT])
            ->whereIn('cocktail_id', $excludedCocktailIds)
            ->where('ingredients.bar_id', $barMembership->bar_id)
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->groupBy('ingredient_id')
            ->get();

        // Bar shelf ingredients
        $barShelfIngredients = DB::table('bar_ingredients')
            ->select('ingredient_id')
            ->where('bar_id', $barMembership->bar_id)
            ->pluck('ingredient_id');

        $potentialCocktails = Cocktail::query()
            ->select('cocktails.*', DB::raw('0 AS recommendation_score'))
            ->whereNotIn('cocktails.id', $excludedCocktailIds)
            ->where('cocktails.bar_id', $barMembership->bar_id)
            ->with('tags', 'ingredients.ingredient', 'images')
            // ->inRandomOrder()
            ->get();

        foreach ($potentialCocktails as $cocktail) {
            $score = 0;

            foreach ($cocktail->tags->pluck('id') as $tagId) {
                $score += $memberFavoriteTags->firstWhere('id', $tagId)->weight ?? 0;

                if (array_key_exists($tagId, $negativeTags)) {
                    $score += self::NEGATIVE_RATING_PENALTY * $negativeTags[$tagId];
                }
            }

            $totalIngredients = $cocktail->ingredients->count();
            $shelfMatches = 0;
            foreach ($cocktail->ingredients->pluck('ingredient_id') as $ingredientId) {
                $score += $memberFavoriteIngredients->firstWhere('id', $ingredientId)->weight ?? 0;

                if ($barShelfIngredients->contains($ingredientId)) {
                    $score += self::BAR_SHELF_INGREDIENT_MATCH_WEIGHT;
                    $shelfMatches++;
                }
            }

            // Shelf completeness bonus (percentage of ingredients user has)
            if ($totalIngredients > 0) {
                $shelfCompleteness = $shelfMatches / $totalIngredients;
                $score += $shelfCompleteness * self::BAR_SHELF_COMPLETE_MATCH_WEIGHT;
            }

            // Recency boost for newer cocktails
            if ($cocktail->created_at && $cocktail->created_at->gt(now()->subMonths(2))) {
                $score += self::RECENCY_BOOST_WEIGHT;
            }

            $cocktail['recommendation_score'] = $score;
        }

        return $potentialCocktails->sortByDesc('recommendation_score')->take($limit);
    }

    /**
     * Get cocktail IDs that should be excluded from recommendations.
     * This includes favorites and rated cocktails.
     *
     * @return array<int>
     */
    private function getExcludedCocktails(BarMembership $barMembership): array
    {
        $favorites = DB::table('cocktail_favorites')
            ->where('bar_membership_id', $barMembership->id)
            ->pluck('cocktail_id')
            ->toArray();

        $rated = DB::table('ratings')
            ->where('user_id', $barMembership->user_id)
            ->where('rateable_type', Cocktail::class)
            ->pluck('rateable_id')
            ->toArray();

        return array_unique(array_merge($favorites, $rated));
    }

    /**
     * Get tags from low-rated cocktails.
     *
     * @return array<int, int>
     */
    private function getNegativeTags(BarMembership $barMembership): array
    {
        $lowRatedCocktails = DB::table('ratings')
            ->select('rateable_id')
            ->where('user_id', $barMembership->user_id)
            ->where('rateable_type', Cocktail::class)
            ->where('rating', '<=', 2)
            ->pluck('rateable_id');

        if ($lowRatedCocktails->isEmpty()) {
            return [];
        }

        return DB::table('cocktail_tag')
            ->select('tag_id', DB::raw('COUNT(*) as frequency'))
            ->whereIn('cocktail_id', $lowRatedCocktails)
            ->groupBy('tag_id')
            ->having('frequency', '>=', 2)
            ->pluck('frequency', 'tag_id')
            ->toArray();
    }
}
