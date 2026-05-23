<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class CreateUtensil
{
    public function __construct(
        public int $barId,
        public string $name,
        public ?string $description = null,
    ) {
    }
}
