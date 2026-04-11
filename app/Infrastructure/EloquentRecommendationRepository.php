<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Recommendation\CocktailWithDetails;
use BarAssistant\Domain\Recommendation\RecommendationRepository;
use BarAssistant\Domain\Recommendation\WeightedIngredient;
use BarAssistant\Domain\Recommendation\WeightedTag;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\Cocktail;

final class EloquentRecommendationRepository implements RecommendationRepository
{
    public function getApplicableCocktails(MemberId $memberId): array
    {
        $barMembership = BarMembership::findOrFail($memberId->value);

        $favorites = DB::table('cocktail_favorites')
            ->where('bar_membership_id', $barMembership->id)
            ->pluck('cocktail_id')
            ->toArray();

        $rated = DB::table('ratings')
            ->where('user_id', $barMembership->user_id)
            ->where('rateable_type', Cocktail::class)
            ->pluck('rateable_id')
            ->toArray();

        $excludedCocktailIds = array_unique(array_merge($favorites, $rated));

        return Cocktail::query()
            ->whereNotIn('cocktails.id', $excludedCocktailIds)
            ->where('cocktails.bar_id', $barMembership->bar_id)
            ->with('tags', 'ingredients.ingredient', 'images')
            ->limit(500)
            ->get()
            ->map(function ($cocktail) {
                return new CocktailWithDetails(
                    cocktailId: new CocktailId($cocktail->id),
                    tags: $cocktail->tags->pluck('name')->toArray(),
                    ingredientIds: $cocktail->ingredients->pluck('ingredient_id')->toArray(),
                    createdAt: $cocktail->created_at->toDateTimeImmutable(),
                );
            })
            ->toArray();
    }

    public function getFavoriteTags(MemberId $memberId): array
    {
        $tags = DB::table('cocktail_favorites')
            ->select('tags.name', DB::raw('COUNT(*) as weight'))
            ->join('cocktail_tag', 'cocktail_tag.cocktail_id', '=', 'cocktail_favorites.cocktail_id')
            ->join('tags', 'tags.id', '=', 'cocktail_tag.tag_id')
            ->where('cocktail_favorites.bar_membership_id', $memberId->value)
            ->groupBy('tags.name')
            ->get();

        if ($tags->isEmpty()) {
            return [];
        }

        $maxWeight = (int) $tags->max('weight');

        return $tags->map(static function ($res) use ($maxWeight) {
            return new WeightedTag($res->name, $res->weight / $maxWeight);
        })->toArray();
    }

    public function getNegativeTags(MemberId $memberId): array
    {
        $lowRatedCocktails = DB::table('ratings')
            ->select('rateable_id')
            ->where('user_id', $memberId->value)
            ->where('rateable_type', Cocktail::class)
            ->where('rating', '<=', 2)
            ->pluck('rateable_id');

        if ($lowRatedCocktails->isEmpty()) {
            return [];
        }

        $tags = DB::table('cocktail_tag')
            ->select('tags.name', DB::raw('COUNT(*) as weight'))
            ->join('tags', 'tags.id', '=', 'cocktail_tag.tag_id')
            ->whereIn('cocktail_id', $lowRatedCocktails)
            ->groupBy('tags.name')
            ->having('weight', '>=', 2)
            ->get();

        if ($tags->isEmpty()) {
            return [];
        }

        $maxWeight = (int) $tags->max('weight');

        return $tags->map(static function ($res) use ($maxWeight) {
            return new WeightedTag($res->name, $res->weight / $maxWeight);
        })->toArray();
    }

    public function getFavoriteIngredients(MemberId $memberId): array
    {
        $favoriteIngredients = DB::table('cocktail_favorites')
            ->selectRaw('ingredients.id, COUNT(cocktail_favorites.cocktail_id) AS weight')
            ->join('cocktail_ingredients', 'cocktail_ingredients.cocktail_id', '=', 'cocktail_favorites.cocktail_id')
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->where('cocktail_favorites.bar_membership_id', $memberId->value)
            ->groupBy('ingredients.id')
            ->get();

        if ($favoriteIngredients->isEmpty()) {
            return [];
        }

        $maxWeight = (int) $favoriteIngredients->max('weight');

        return $favoriteIngredients->map(static function ($res) use ($maxWeight) {
            return new WeightedIngredient(new IngredientId($res->id), $res->weight / $maxWeight);
        })->toArray();
    }

    public function getBarInventoryIngredients(BarId $barId): array
    {
        throw new \Exception('Not implemented');
    }
}
