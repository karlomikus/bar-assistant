<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

final readonly class WeightedTag
{
    public function __construct(
        public string $tagName,
        public float $weight,
    ) {
    }
}
