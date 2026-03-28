<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;

interface RecommendationRepository
{
    /**
     * Get all cocktails in a bar with their tags and ingredients, excluding specified IDs
     *
     * @param CocktailId[] $excludeIds
     * @return CocktailWithDetails[]
     */
    public function getCocktailsWithDetails(BarId $barId, array $excludeIds): array;

    /**
     * Get cocktail IDs that the member has favorited
     *
     * @return CocktailId[]
     */
    public function getFavoriteCocktailIds(MemberId $memberId): array;

    /**
     * Get cocktail IDs that the member has rated
     *
     * @return CocktailId[]
     */
    public function getRatedCocktailIds(MemberId $memberId): array;

    /**
     * Get weighted tags from member's favorite cocktails
     *
     * @return WeightedTag[]
     */
    public function getFavoriteTags(MemberId $memberId, BarId $barId): array;

    /**
     * Get weighted tags from member's low-rated cocktails (negative signals)
     *
     * @return WeightedTag[]
     */
    public function getNegativeTags(MemberId $memberId, BarId $barId): array;

    /**
     * Get weighted ingredients from member's favorite cocktails
     *
     * @return WeightedIngredient[]
     */
    public function getFavoriteIngredients(MemberId $memberId, BarId $barId): array;

    /**
     * Get ingredient IDs available on the bar's shelf
     *
     * @return IngredientId[]
     */
    public function getBarShelfIngredientIds(BarId $barId): array;
}
