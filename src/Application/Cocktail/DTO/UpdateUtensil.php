<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class UpdateUtensil
{
    public function __construct(
        public int $utensilId,
        public string $name,
        public ?string $description = null,
    ) {
    }
}
