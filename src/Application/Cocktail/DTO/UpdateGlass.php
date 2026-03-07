<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class UpdateGlass
{
    /**
     * @param int[] $images
     */
    public function __construct(
        public int $glassId,
        public string $name,
        public ?string $description = null,
        public ?float $volume = null,
        public ?string $units = null,
        public array $images = [],
    ) {
    }
}
