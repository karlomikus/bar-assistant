<?php

declare(strict_types=1);

namespace BarAssistant\Application\User\DTO;

final readonly class AnonymizeUserRequest
{
    public function __construct(
        public int $userId,
    ) {
    }
}
