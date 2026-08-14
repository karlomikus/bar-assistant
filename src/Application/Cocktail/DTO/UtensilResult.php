<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

use BarAssistant\Domain\Cocktail\Utensil;

final readonly class UtensilResult
{
    public function __construct(
        public int $id,
        public int $barId,
        public string $name,
        public ?string $description = null,
    ) {
    }

    public static function fromUtensil(Utensil $utensil): self
    {
        return new self(
            id: $utensil->getId()->value ?? 0,
            barId: $utensil->getBarId()->value,
            name: $utensil->getName()->toString(),
            description: $utensil->getDescription(),
        );
    }
}
