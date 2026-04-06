<?php

declare(strict_types=1);

namespace BarAssistant\Application\User\DTO;

use SensitiveParameter;

final readonly class ChangePasswordRequest
{
    public function __construct(
        public int $userId,
        #[SensitiveParameter]
        public string $newPasswordHash,
    ) {
    }
}
