<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

final readonly class UpdateBarRequest
{
    /**
     * @param int[] $images
     */
    public function __construct(
        public int $barId,
        public string $name,
        public int $userId,
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
