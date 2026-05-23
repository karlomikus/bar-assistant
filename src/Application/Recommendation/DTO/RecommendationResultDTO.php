<?php

declare(strict_types=1);

namespace BarAssistant\Application\Recommendation\DTO;

final readonly class RecommendationResultDTO
{
    public function __construct(
        public int $cocktailId,
        public float $score,
    ) {
    }
}
