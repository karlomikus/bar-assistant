<?php

declare(strict_types=1);

namespace BarAssistant\Application\Recommendation\DTO;

use BarAssistant\Domain\Recommendation\WeightedTag;
use BarAssistant\Domain\Recommendation\AbvBucketStat;

final readonly class UserTasteProfileDTO
{
    /**
     * @param WeightedTag[] $favoriteTags
     * @param WeightedTag[] $negativeTags
     * @param array<array{ingredientId: int, weight: float}> $favoriteIngredients
     * @param AbvBucketStat[] $abvDistribution
     */
    public function __construct(
        public array $favoriteTags,
        public array $negativeTags,
        public array $favoriteIngredients,
        public ?float $averageAbv,
        public array $abvDistribution,
    ) {
    }
}
