<?php

declare(strict_types=1);

namespace BarAssistant\Application\Review\DTO;

final readonly class ReviewResult
{
    public function __construct(
        public int $id,
    ) {
    }
}
