<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

use BarAssistant\Domain\Ingredient\IngredientId;

final class RecommendationScoringService
{
    /** Contribution of normalized tag-preference signal to final score */
    private const float TAG_PREFERENCE_WEIGHT = 0.25;

    /** Contribution of normalized ingredient-preference signal to final score */
    private const float INGREDIENT_PREFERENCE_WEIGHT = 0.35;

    /** Contribution of shelf-coverage signal (can the user actually make this?) */
    private const float SHELF_COVERAGE_WEIGHT = 0.30;

    /** Flat bonus for recently-added cocktails; intentionally small to not override preference signals */
    private const float RECENCY_BOOST_WEIGHT = 0.15;

    private const int RECENCY_MONTHS = 3;

    /**
     * Calculate recommendation scores for cocktails
     *
     * @param WeightedTag[] $favoriteTags
     * @param WeightedTag[] $negativeTags
     * @param WeightedIngredient[] $favoriteIngredients
     * @param IngredientId[] $barShelfIngredientIds
     * @param CocktailWithDetails[] $cocktails
     * @return RecommendationResult[]
     */
    public function score(
        array $favoriteTags,
        array $negativeTags,
        array $favoriteIngredients,
        array $barShelfIngredientIds,
        array $cocktails,
    ): array {
        $favoriteTagMap = [];
        foreach ($favoriteTags as $tag) {
            $favoriteTagMap[$tag->tagName] = $tag->weight;
        }

        $negativeTagMap = [];
        foreach ($negativeTags as $tag) {
            $negativeTagMap[$tag->tagName] = $tag->weight;
        }

        $favoriteIngredientMap = [];
        foreach ($favoriteIngredients as $ingredient) {
            $favoriteIngredientMap[$ingredient->ingredientId->value] = $ingredient->weight;
        }

        $barShelfIngredientIdValues = array_map(
            fn (IngredientId $id) => $id->value,
            $barShelfIngredientIds,
        );
        $barShelfIngredientSet = array_flip($barShelfIngredientIdValues);

        $results = [];

        foreach ($cocktails as $cocktail) {
            $shelfMatches = 0;

            // --- Tag preference signal (normalized by tag count) ---
            $totalTags = count($cocktail->tags);
            $tagWeightSum = 0.0;
            $negativeTagWeightSum = 0.0;
            foreach ($cocktail->tags as $tagName) {
                if (isset($favoriteTagMap[$tagName])) {
                    $tagWeightSum += $favoriteTagMap[$tagName];
                }

                if (isset($negativeTagMap[$tagName])) {
                    $negativeTagWeightSum += $negativeTagMap[$tagName];
                }
            }

            // Negative tag score is used as a dampener
            $negativeTagScore = $totalTags > 0 ? $negativeTagWeightSum / $totalTags : 0.0;
            $tagScore = max(0.0, ($totalTags > 0 ? $tagWeightSum / $totalTags : 0.0) - $negativeTagScore);

            // --- Ingredient preference + shelf coverage signals (normalized by ingredient count) ---
            $totalIngredients = count($cocktail->ingredientIds);
            $ingredientWeightSum = 0.0;
            foreach ($cocktail->ingredientIds as $ingredientId) {
                $ingredientValue = $ingredientId->value;

                if (isset($favoriteIngredientMap[$ingredientValue])) {
                    $ingredientWeightSum += $favoriteIngredientMap[$ingredientValue];
                }

                if (isset($barShelfIngredientSet[$ingredientValue])) {
                    $shelfMatches++;
                }
            }
            $ingredientScore = $totalIngredients > 0 ? $ingredientWeightSum / $totalIngredients : 0.0;
            $shelfCompleteness = $totalIngredients > 0 ? $shelfMatches / $totalIngredients : 0.0;

            // --- Recency signal ---
            $recencyBoost = ($cocktail->createdAt !== null && $cocktail->createdAt > new \DateTimeImmutable('-' . self::RECENCY_MONTHS . ' months'))
                ? self::RECENCY_BOOST_WEIGHT
                : 0.0;

            // --- Blend normalized signals ---
            $score = ($tagScore * self::TAG_PREFERENCE_WEIGHT)
                + ($ingredientScore * self::INGREDIENT_PREFERENCE_WEIGHT)
                + ($shelfCompleteness * self::SHELF_COVERAGE_WEIGHT)
                + $recencyBoost;

            $results[] = new RecommendationResult(
                cocktailId: $cocktail->cocktailId,
                score: $score,
            );
        }

        usort($results, fn (RecommendationResult $a, RecommendationResult $b) => $b->score <=> $a->score);

        return $results;
    }
}
