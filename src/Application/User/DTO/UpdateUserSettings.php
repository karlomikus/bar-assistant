<?php

declare(strict_types=1);

namespace BarAssistant\Application\User\DTO;

final readonly class UpdateUserSettings
{
    public function __construct(
        public int $userId,
        public ?string $language = null,
        public ?string $theme = null,
    ) {
    }
}
