<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

final readonly class CreateMemberInventoryRequest
{
    public function __construct(
        public int $memberId,
        public int $userId,
        public string $name,
    ) {
    }
}
