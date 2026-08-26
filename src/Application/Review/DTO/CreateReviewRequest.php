<?php

declare(strict_types=1);

namespace BarAssistant\Application\Review\DTO;

final readonly class CreateReviewRequest
{
    public function __construct(
        public int $barMembershipId,
        public int $cocktailId,
        public string $content,
    ) {
    }
}
