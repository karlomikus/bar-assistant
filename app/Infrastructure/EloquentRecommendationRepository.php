<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use BarAssistant\Domain\Bar\MemberId;
use Kami\Cocktail\Models\BarMembership;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Recommendation\WeightedTag;
use BarAssistant\Domain\Recommendation\WeightedIngredient;
use BarAssistant\Domain\Recommendation\CocktailWithDetails;
use BarAssistant\Domain\Recommendation\RecommendationRepository;

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
            ->where('bar_membership_id', $barMembership->id)
            ->where('rateable_type', Cocktail::class)
            ->pluck('rateable_id')
            ->toArray();

        if (count($favorites) === 0 && count($rated) === 0) {
            return [];
        }

        $excludedCocktailIds = array_unique(array_merge($favorites, $rated));

        return Cocktail::query()
            ->whereNotIn('cocktails.id', $excludedCocktailIds)
            ->where('cocktails.bar_id', $barMembership->bar_id)
            ->with('tags', 'ingredients')
            ->get()
            ->map(static function (Cocktail $cocktail): CocktailWithDetails {
                return new CocktailWithDetails(
                    cocktailId: new CocktailId($cocktail->id),
                    tags: $cocktail->tags->pluck('name')->toArray(),
                    ingredientIds: $cocktail->ingredients->pluck('ingredient_id')->map(static fn ($id): IngredientId => new IngredientId(($id)))->toArray(),
                    createdAt: $cocktail->created_at->toDateTimeImmutable(),
                );
            })
            ->toArray();
    }

    public function getFavoriteTags(MemberId $memberId): array
    {
        $barMembership = BarMembership::findOrFail($memberId->value);

        $favoriteCocktailIds = DB::table('cocktail_favorites')
            ->where('bar_membership_id', $barMembership->id)
            ->pluck('cocktail_id')
            ->toArray();

        $highRatedCocktailIds = DB::table('ratings')
            ->where('user_id', $barMembership->user_id)
            ->where('rateable_type', Cocktail::class)
            ->where('rating', '>=', 4)
            ->pluck('rateable_id')
            ->toArray();

        $preferredCocktailIds = array_unique(array_merge($favoriteCocktailIds, $highRatedCocktailIds));

        if ($preferredCocktailIds === []) {
            return [];
        }

        $tags = DB::table('cocktail_tag')
            ->select('tags.name', DB::raw('COUNT(*) as weight'))
            ->join('tags', 'tags.id', '=', 'cocktail_tag.tag_id')
            ->whereIn('cocktail_tag.cocktail_id', $preferredCocktailIds)
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
        $barMembership = BarMembership::findOrFail($memberId->value);

        $lowRatedCocktails = DB::table('ratings')
            ->select('rateable_id')
            ->where('bar_membership_id', $barMembership->id)
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
        $barMembership = BarMembership::findOrFail($memberId->value);

        $favoriteCocktailIds = DB::table('cocktail_favorites')
            ->where('bar_membership_id', $barMembership->id)
            ->pluck('cocktail_id')
            ->toArray();

        $highRatedCocktailIds = DB::table('ratings')
            ->where('bar_membership_id', $barMembership->id)
            ->where('rateable_type', Cocktail::class)
            ->where('rating', '>=', 4)
            ->pluck('rateable_id')
            ->toArray();

        $preferredCocktailIds = array_unique(array_merge($favoriteCocktailIds, $highRatedCocktailIds));

        if ($preferredCocktailIds === []) {
            return [];
        }

        $favoriteIngredients = DB::table('cocktail_ingredients')
            ->selectRaw('ingredients.id, COUNT(cocktail_ingredients.cocktail_id) AS total')
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->whereIn('cocktail_ingredients.cocktail_id', $preferredCocktailIds)
            ->groupBy('ingredients.id')
            ->get();

        if ($favoriteIngredients->isEmpty()) {
            return [];
        }

        $maxIngredients = (int) $favoriteIngredients->max('total');

        return $favoriteIngredients->map(static function ($res) use ($maxIngredients): WeightedIngredient {
            return new WeightedIngredient(new IngredientId($res->id), $res->total / $maxIngredients);
        })->toArray();
    }
}
