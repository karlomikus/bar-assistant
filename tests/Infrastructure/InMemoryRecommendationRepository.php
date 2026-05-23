<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Recommendation\WeightedTag;
use BarAssistant\Domain\Recommendation\WeightedIngredient;
use BarAssistant\Domain\Recommendation\CocktailWithDetails;
use BarAssistant\Domain\Recommendation\RecommendationRepository;

final class InMemoryRecommendationRepository implements RecommendationRepository
{
    /**
     * @param CocktailWithDetails[] $cocktails
     * @param CocktailId[] $favoriteIds
     * @param CocktailId[] $ratedIds
     * @param WeightedTag[] $favoriteTags
     * @param WeightedTag[] $negativeTags
     * @param WeightedIngredient[] $favoriteIngredients
     * @param IngredientId[] $barShelfIngredients
     */
    public function __construct(
        private array $cocktails = [],
        private array $favoriteIds = [],
        private array $ratedIds = [],
        private array $favoriteTags = [],
        private array $negativeTags = [],
        private array $favoriteIngredients = [],
        private array $barShelfIngredients = [],
    ) {
    }

    /**
     * @param CocktailId[] $excludeIds
     * @return CocktailWithDetails[]
     */
    public function getApplicableCocktails(BarId $barId, array $excludeIds): array
    {
        $excludeValues = array_map(
            fn (CocktailId $id) => $id->value,
            $excludeIds,
        );

        return array_values(array_filter(
            $this->cocktails,
            fn (CocktailWithDetails $c) => !in_array($c->cocktailId->value, $excludeValues, true),
        ));
    }

    /**
     * @return CocktailId[]
     */
    public function getFavoriteCocktailIds(MemberId $memberId): array
    {
        return $this->favoriteIds;
    }

    /**
     * @return CocktailId[]
     */
    public function getRatedCocktailIds(MemberId $memberId): array
    {
        return $this->ratedIds;
    }

    /**
     * @return WeightedTag[]
     */
    public function getFavoriteTags(MemberId $memberId, BarId $barId): array
    {
        return $this->favoriteTags;
    }

    /**
     * @return WeightedTag[]
     */
    public function getNegativeTags(MemberId $memberId, BarId $barId): array
    {
        return $this->negativeTags;
    }

    /**
     * @return WeightedIngredient[]
     */
    public function getFavoriteIngredients(MemberId $memberId, BarId $barId): array
    {
        return $this->favoriteIngredients;
    }

    /**
     * @return IngredientId[]
     */
    public function getBarInventoryIngredients(BarId $barId): array
    {
        return $this->barShelfIngredients;
    }
}
