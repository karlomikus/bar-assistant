<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

use BarAssistant\Domain\Ingredient\IngredientId;

final class RecommendationScoringService
{
    private const float BAR_SHELF_INGREDIENT_MATCH_WEIGHT = 0.7;
    private const float BAR_SHELF_COMPLETE_MATCH_WEIGHT = 1.0;
    private const float RECENCY_BOOST_WEIGHT = 0.25;
    private const float NEGATIVE_RATING_PENALTY = -0.5;

    private const int RECENCY_MONTHS = 4;

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
            $score = 0.0;
            $matchedTagIds = [];
            $matchedIngredientIds = [];
            $shelfMatches = 0;

            $cocktailTagIds = $cocktail->tags;
            foreach ($cocktailTagIds as $tagId) {
                if (isset($favoriteTagMap[$tagId])) {
                    $score += $favoriteTagMap[$tagId];
                    $matchedTagIds[] = $tagId;
                }

                if (isset($negativeTagMap[$tagId])) {
                    $score += self::NEGATIVE_RATING_PENALTY * $negativeTagMap[$tagId];
                }
            }

            $totalIngredients = count($cocktail->ingredientIds);
            foreach ($cocktail->ingredientIds as $ingredientId) {
                $ingredientValue = $ingredientId->value;

                if (isset($favoriteIngredientMap[$ingredientValue])) {
                    $score += $favoriteIngredientMap[$ingredientValue];
                    $matchedIngredientIds[] = $ingredientValue;
                }

                if (isset($barShelfIngredientSet[$ingredientValue])) {
                    $score += self::BAR_SHELF_INGREDIENT_MATCH_WEIGHT;
                    $shelfMatches++;
                }
            }

            if ($totalIngredients > 0) {
                $shelfCompleteness = $shelfMatches / $totalIngredients;
                $score += $shelfCompleteness * self::BAR_SHELF_COMPLETE_MATCH_WEIGHT;
            }

            if ($cocktail->createdAt !== null && $cocktail->createdAt > new \DateTimeImmutable('-' . self::RECENCY_MONTHS . ' months')) {
                $score += self::RECENCY_BOOST_WEIGHT;
            }

            $results[] = new RecommendationResult(
                cocktailId: $cocktail->cocktailId,
                score: $score,
                matchedTagIds: $matchedTagIds,
                matchedIngredientIds: $matchedIngredientIds,
                shelfCompleteness: $totalIngredients > 0 ? $shelfMatches / $totalIngredients : 0.0,
            );
        }

        usort($results, fn (RecommendationResult $a, RecommendationResult $b) => $b->score <=> $a->score);

        return $results;
    }
}
