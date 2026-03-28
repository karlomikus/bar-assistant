<?php

declare(strict_types=1);

namespace BarAssistant\Application\Recommendation\DTO;

final readonly class RecommendationResultDTO
{
    /**
     * @param int[] $matchedTagIds
     * @param int[] $matchedIngredientIds
     */
    public function __construct(
        public int $cocktailId,
        public float $score,
        public array $matchedTagIds,
        public array $matchedIngredientIds,
        public float $shelfCompleteness,
    ) {
    }
}
