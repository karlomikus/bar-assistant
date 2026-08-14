<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

use BarAssistant\Domain\Cocktail\CocktailMethod;

final readonly class CocktailMethodResult
{
    public function __construct(
        public int $id,
        public int $barId,
        public string $name,
        public float $dilutionPercentage,
        public ?string $description = null,
    ) {
    }

    public static function fromCocktailMethod(CocktailMethod $cocktailMethod): self
    {
        return new self(
            id: $cocktailMethod->getId()->value ?? 0,
            barId: $cocktailMethod->getBarId()->value,
            name: $cocktailMethod->getName()->toString(),
            dilutionPercentage: $cocktailMethod->getDilution()->toFloat(),
            description: $cocktailMethod->getDescription(),
        );
    }
}
