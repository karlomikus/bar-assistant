<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class CreateCollection
{
    /**
     * @param int[] $cocktailIds
     */
    public function __construct(
        public int $barId,
        public int $memberId,
        public string $name,
        public ?string $description = null,
        public bool $isBarShared = false,
        public array $cocktailIds = [],
    ) {
    }
}
