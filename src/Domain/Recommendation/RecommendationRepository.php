<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

use BarAssistant\Domain\Bar\MemberId;

interface RecommendationRepository
{
    /**
     * Get ABV preference derived from member's favorite and high-rated cocktails.
     * Returns a preference with null averageAbv and empty distribution when no preferred cocktails exist.
     */
    public function getAbvPreference(MemberId $memberId): UserAbvPreference;

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
    public function getFavoriteTags(MemberId $memberId, int $limit = 2000): array;

    /**
     * Get weighted tags from member's low-rated cocktails (negative signals)
     *
     * @return WeightedTag[]
     */
    public function getNegativeTags(MemberId $memberId, int $limit = 2000): array;

    /**
     * Get weighted ingredients from member's favorite cocktails
     *
     * @return WeightedIngredient[]
     */
    public function getFavoriteIngredients(MemberId $memberId, int $limit = 2000): array;

    /**
     * Get tags with cocktail counts from member's favorite cocktails, sorted by count descending
     *
     * @return CocktailTagCount[]
     */
    public function getPositiveTagCocktailCounts(MemberId $memberId, int $limit = 12): array;

    /**
     * Get tags with cocktail counts from member's disliked cocktails, sorted by count descending
     *
     * @return CocktailTagCount[]
     */
    public function getNegativeTagCocktailCounts(MemberId $memberId, int $limit = 12): array;
}
