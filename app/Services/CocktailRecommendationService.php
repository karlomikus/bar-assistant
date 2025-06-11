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
    // private const FAVORITES_MATCH_WEIGHT = 0.2; // TODO

    /**
     * @return \Illuminate\Support\Collection<array-key, Cocktail>
     */
    public function recommend(BarMembership $barMembership, int $limit = 10): Collection
    {
        $barMembership->loadMissing('cocktailFavorites');

        // We dont want to list cocktails that user has already favorited or rated
        $memberFavoriteCocktailIds = $barMembership->cocktailFavorites->pluck('cocktail_id')->toArray();
        $memberRatedCocktailIds = DB::table('ratings')
            ->select('rateable_id')
            ->where('rateable_type', Cocktail::class)
            ->where('user_id', $barMembership->user_id)
            ->pluck('rateable_id');

        // Collect all favorite tags
        $memberFavoriteTags = DB::table('tags')
            ->selectRaw('tags.id, COUNT(cocktail_favorites.cocktail_id) * ? AS weight', [self::TAG_MATCH_WEIGHT])
            ->join('cocktail_tag', 'cocktail_tag.tag_id', '=', 'tags.id')
            ->join('cocktail_favorites', 'cocktail_favorites.cocktail_id', '=', 'cocktail_tag.cocktail_id')
            ->where('cocktail_favorites.bar_membership_id', $barMembership->id)
            ->groupBy('tags.id')
            ->orderBy('cocktails_count', 'DESC')
            ->get();

        // Collect all favorite ingredients
        $memberFavoriteIngredients = DB::table('cocktail_ingredients')
            ->selectRaw('ingredients.id, COUNT(cocktail_id) * ? AS weight', [self::INGREDIENT_MATCH_WEIGHT])
            ->whereIn('cocktail_id', $memberFavoriteCocktailIds)
            ->where('ingredients.bar_id', $barMembership->bar_id)
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->groupBy('ingredient_id')
            ->orderBy('cocktails_count', 'DESC')
            ->get();

        // TODO: Prefer shelf cocktails
        // TODO: Find cocktail tags with poor ratings
        // TODO: Take in account number of ingredients

        $potentialCocktails = Cocktail::query()
            ->select('cocktails.*', DB::raw('0 AS recommendation_score'))
            ->whereNotIn('cocktails.id', $memberFavoriteCocktailIds)
            ->whereNotIn('cocktails.id', $memberRatedCocktailIds)
            ->where('cocktails.bar_id', $barMembership->bar_id)
            ->limit(150)
            ->with('tags', 'ingredients.ingredient', 'images')
            ->inRandomOrder()
            ->get();

        foreach ($potentialCocktails as $cocktail) {
            $score = 0;

            foreach ($cocktail->tags->pluck('id') as $tagId) {
                $score += $memberFavoriteTags->firstWhere('id', $tagId)->weight ?? 0;
            }

            foreach ($cocktail->ingredients->pluck('ingredient_id') as $ingredientId) {
                $score += $memberFavoriteIngredients->firstWhere('id', $ingredientId)->weight ?? 0;
            }

            $cocktail['recommendation_score'] = $score;
        }

        return $potentialCocktails->sortByDesc('recommendation_score')->take($limit);
    }
}
