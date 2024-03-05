<?php

declare(strict_types=1);

namespace Kami\Cocktail\DataObjects\Cocktail;

readonly class Ingredient
{
    /**
     * @param array<Substitute> $substitutes
     */
    public function __construct(
        public int $id,
        public ?string $name,
        public float $amount,
        public string $units,
        public int $sort = 0,
        public bool $optional = false,
        public array $substitutes = [],
        public ?float $amountMax = null,
        public ?string $note = null
    ) {
    }
}
