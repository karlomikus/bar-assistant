<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class CreateCocktailMethod
{
    public function __construct(
        public int $barId,
        public string $name,
        public float $dilutionPercentage,
        public ?string $description = null,
    ) {
    }
}
