<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class UpdateCocktailMethod
{
    public function __construct(
        public int $id,
        public string $name,
        public float $dilutionPercentage,
        public ?string $description = null,
    ) {
    }
}
