<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

final readonly class CreateBarRequest
{
    /**
     * @param int[] $images
     */
    public function __construct(
        public string $name,
        public int $createdUserId,
        public ?string $subtitle = null,
        public ?string $description = null,
        public ?bool $isPublic = null,
        public ?bool $isInviteCodeEnabled = null,
        public array $images = [],
        public ?string $defaultUnits = null,
        public ?string $defaultCurrency = null,
    ) {
    }
}
