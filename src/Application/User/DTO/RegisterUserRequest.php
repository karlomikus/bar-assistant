<?php

declare(strict_types=1);

namespace BarAssistant\Application\User\DTO;

final readonly class RegisterUserRequest
{
    public function __construct(
        public string $email,
        public string $name,
        public bool $confirmAccount = false,
    ) {
    }
}
