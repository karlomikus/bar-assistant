<?php

declare(strict_types=1);

namespace BarAssistant\Application\Recommendation\DTO;

final readonly class GetRecommendationsRequest
{
    public function __construct(
        public int $memberId,
        public int $limit = 10,
    ) {
    }
}
