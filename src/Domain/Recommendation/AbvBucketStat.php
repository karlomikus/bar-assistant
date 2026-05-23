<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

final readonly class AbvBucketStat
{
    public function __construct(
        public string $bucket,
        public int $count,
        public float $ratio,
    ) {
    }
}
