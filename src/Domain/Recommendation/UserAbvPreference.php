<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

final readonly class UserAbvPreference
{
    /**
     * @param AbvBucketStat[] $distribution
     */
    public function __construct(
        public ?float $averageAbv,
        public array $distribution,
    ) {
    }
}
