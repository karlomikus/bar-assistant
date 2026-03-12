<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;

final readonly class UpdateBarRequest
{
    public function __construct(
        public BarId $barId,
        public Name $name,
        public ?string $subtitle = null,
        public ?string $description = null,
        public ?bool $isPublic = null,
        public ?bool $isInviteCodeEnabled = null,
    ) {
    }
}
