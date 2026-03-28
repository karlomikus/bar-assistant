<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

use BarAssistant\Domain\Cocktail\CocktailId;

final readonly class RecommendationResult
{
    /**
     * @param int[] $matchedTagIds
     * @param int[] $matchedIngredientIds
     */
    public function __construct(
        public CocktailId $cocktailId,
        public float $score,
        public array $matchedTagIds,
        public array $matchedIngredientIds,
        public float $shelfCompleteness,
    ) {
    }
}
