<?php

declare(strict_types=1);

namespace BarAssistant\Application\User\DTO;

final readonly class UpdateUserProfile
{
    public function __construct(
        public int $userId,
        public string $name,
    ) {
    }
}
