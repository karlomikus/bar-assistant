<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class UpdateCollection
{
    public function __construct(
        public int $collectionId,
        public string $name,
        public ?string $description = null,
        public bool $isBarShared = false,
    ) {
    }
}
