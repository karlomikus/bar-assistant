<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

use BarAssistant\Domain\Common\Name;

final readonly class CreateBarRequest
{
    public function __construct(
        public string $name,
        public int $createdUserId,
        public ?string $subtitle = null,
        public ?string $description = null,
        public ?bool $isPublic = null,
        public ?bool $isInviteCodeEnabled = null,
        public array $images = [],
    ) {
    }
}
