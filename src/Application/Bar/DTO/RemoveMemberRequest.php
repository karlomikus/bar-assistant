<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

readonly class RemoveMemberRequest
{
    public function __construct(
        public int $userId,
        public int $barId,
    ) {
    }
}
