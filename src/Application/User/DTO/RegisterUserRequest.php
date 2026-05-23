<?php

declare(strict_types=1);

namespace BarAssistant\Application\User\DTO;

use SensitiveParameter;

final readonly class RegisterUserRequest
{
    public function __construct(
        public string $name,
        public string $email,
        #[SensitiveParameter]
        public string $passwordHash,
        public bool $confirmAccount = false,
    ) {
    }
}
