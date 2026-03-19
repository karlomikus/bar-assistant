<?php

declare(strict_types=1);

namespace BarAssistant\Application\User\DTO;

final readonly class RegisterUserRequest
{
    public function __construct(
        public string $name,
        public string $email,
        public string $passwordHash,
        public bool $confirmAccount = false,
    ) {
    }
}
