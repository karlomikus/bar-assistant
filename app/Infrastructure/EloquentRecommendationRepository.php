<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Bar\MemberId;
use Kami\Cocktail\Models\BarMembership;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Recommendation\WeightedTag;
use BarAssistant\Domain\Recommendation\AbvBucketStat;
use BarAssistant\Domain\Recommendation\CocktailTagCount;
use BarAssistant\Domain\Recommendation\UserAbvPreference;
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

    public function getFavoriteTags(MemberId $memberId, int $limit = 2000): array
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
            ->limit($limit)
            ->get();

        if ($tags->isEmpty()) {
            return [];
        }

        $maxWeight = (int) $tags->max('weight');

        return $tags->map(static function ($res) use ($maxWeight) {
            return new WeightedTag($res->name, $res->weight / $maxWeight);
        })->sortByDesc('weight')->values()->toArray();
    }

    public function getNegativeTags(MemberId $memberId, int $limit = 2000): array
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
            ->limit($limit)
            ->get();

        if ($tags->isEmpty()) {
            return [];
        }

        $maxWeight = (int) $tags->max('weight');

        return $tags->map(static function ($res) use ($maxWeight) {
            return new WeightedTag($res->name, $res->weight / $maxWeight);
        })->sortByDesc('weight')->values()->toArray();
    }

    public function getFavoriteIngredients(MemberId $memberId, int $limit = 2000): array
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
            ->selectRaw('ingredients.id, ingredients.name, COUNT(cocktail_ingredients.cocktail_id) AS total')
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->whereIn('cocktail_ingredients.cocktail_id', $preferredCocktailIds)
            ->groupBy('ingredients.id')
            ->limit($limit)
            ->get();

        if ($favoriteIngredients->isEmpty()) {
            return [];
        }

        $maxIngredients = (int) $favoriteIngredients->max('total');

        return $favoriteIngredients->map(static function ($res) use ($maxIngredients): WeightedIngredient {
            return new WeightedIngredient(new IngredientId($res->id), Name::fromString($res->name), $res->total / $maxIngredients);
        })->sortByDesc('weight')->values()->toArray();
    }

    public function getAbvPreference(MemberId $memberId): UserAbvPreference
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
            return new UserAbvPreference(null, []);
        }

        $abvValues = Cocktail::whereIn('id', $preferredCocktailIds)
            ->with('method', 'ingredients.ingredient')
            ->get()
            ->map(fn (Cocktail $cocktail) => $cocktail->getABV())
            ->filter(fn (?float $abv) => $abv !== null)
            ->values();

        if ($abvValues->isEmpty()) {
            return new UserAbvPreference(null, []);
        }

        $averageAbv = (float) round((float) $abvValues->avg(), 2);
        $total = $abvValues->count();

        $bucketCounts = ['low' => 0, 'medium' => 0, 'high' => 0];
        foreach ($abvValues as $abv) {
            if ($abv <= 13) {
                $bucketCounts['low']++;
            } elseif ($abv <= 20) {
                $bucketCounts['medium']++;
            } else {
                $bucketCounts['high']++;
            }
        }

        $distribution = array_map(
            fn (string $bucket, int $count) => new AbvBucketStat($bucket, $count, round($count / $total, 4)),
            array_keys($bucketCounts),
            $bucketCounts,
        );

        return new UserAbvPreference($averageAbv, $distribution);
    }

    public function getPositiveTagCocktailCounts(MemberId $memberId, int $limit = 12): array
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

        $tags = DB::table('cocktail_tag')
            ->select('tags.name', DB::raw('COUNT(*) as cocktail_count'))
            ->join('tags', 'tags.id', '=', 'cocktail_tag.tag_id')
            ->whereIn('cocktail_tag.cocktail_id', $preferredCocktailIds)
            ->groupBy('tags.name')
            ->orderBy('cocktail_count', 'desc')
            ->limit($limit)
            ->get();

        return $tags->map(fn ($tag) => new CocktailTagCount(
            name: Name::fromString($tag->name),
            count: (int) $tag->cocktail_count,
        ))->toArray();
    }

    public function getNegativeTagCocktailCounts(MemberId $memberId, int $limit = 12): array
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
            ->select('tags.name', DB::raw('COUNT(*) as cocktail_count'))
            ->join('tags', 'tags.id', '=', 'cocktail_tag.tag_id')
            ->whereIn('cocktail_id', $lowRatedCocktails)
            ->groupBy('tags.name')
            ->orderBy('cocktail_count', 'desc')
            ->limit($limit)
            ->get();

        return $tags->map(fn ($tag) => new CocktailTagCount(
            name: Name::fromString($tag->name),
            count: (int) $tag->cocktail_count,
        ))->toArray();
    }
}
