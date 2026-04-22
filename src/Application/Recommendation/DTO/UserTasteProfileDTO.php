<?php

declare(strict_types=1);

namespace BarAssistant\Application\Recommendation\DTO;

use BarAssistant\Domain\Recommendation\AbvBucketStat;

final readonly class UserTasteProfileDTO
{
    /**
     * @param array<array{name: string, count: int}> $favoriteCocktailTags
     * @param array<array{name: string, count: int}> $dislikedCocktailTags
     * @param AbvBucketStat[] $abvDistribution
     */
    public function __construct(
        public array $favoriteCocktailTags,
        public array $dislikedCocktailTags,
        public ?float $averageAbv,
        public array $abvDistribution,
    ) {
    }
}
