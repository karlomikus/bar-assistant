<?php

declare(strict_types=1);

namespace BarAssistant\Application\Recommendation\DTO;

final readonly class GetUserTasteProfileRequest
{
    public function __construct(
        public int $memberId,
    ) {
    }
}
