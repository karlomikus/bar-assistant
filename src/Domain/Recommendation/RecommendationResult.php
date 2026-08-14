<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

use BarAssistant\Domain\Cocktail\CocktailId;

final readonly class RecommendationResult
{
    public function __construct(
        public CocktailId $cocktailId,
        public float $score,
    ) {
    }
}
