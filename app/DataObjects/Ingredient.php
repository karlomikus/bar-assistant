<?php

declare(strict_types=1);

namespace Kami\Cocktail\DataObjects;

class Ingredient
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $amount,
        public readonly string $units,
        public readonly int $sort = 0,
        public readonly bool $optional = false,
        public readonly array $substitutes = [],
    ) {
    }
}
