<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

use BarAssistant\Domain\Bar\MemberId;

interface RecommendationRepository
{
    /**
     * Get all cocktails that are applicable for recommendation.
     *
     * For example, exclude the cocktails that user already favorited or rated.
     *
     * @return CocktailWithDetails[]
     */
    public function getApplicableCocktails(MemberId $memberId): array;

    /**
     * Get weighted tags from member's favorite cocktails
     *
     * @return WeightedTag[]
     */
    public function getFavoriteTags(MemberId $memberId): array;

    /**
     * Get weighted tags from member's low-rated cocktails (negative signals)
     *
     * @return WeightedTag[]
     */
    public function getNegativeTags(MemberId $memberId): array;

    /**
     * Get weighted ingredients from member's favorite cocktails
     *
     * @return WeightedIngredient[]
     */
    public function getFavoriteIngredients(MemberId $memberId): array;
}
