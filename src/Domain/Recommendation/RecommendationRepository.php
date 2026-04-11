<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Ingredient\IngredientId;

interface RecommendationRepository
{
    /**
     * Get all cocktails in a bar with their tags and ingredients, excluding specified IDs
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

    /**
     * Get ingredient IDs available on the bar's shelf
     *
     * @return IngredientId[]
     */
    public function getBarInventoryIngredients(BarId $barId): array;
}
