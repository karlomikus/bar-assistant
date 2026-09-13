<?php

declare(strict_types=1);

namespace BarAssistant\Application\Rating\DTO;

use BarAssistant\Domain\Rating\RateableType;

final readonly class RateRequest
{
    public function __construct(
        public int $barMembershipId,
        public int $rateableId,
        public RateableType $type,
        public float $value,
    ) {
    }
}
