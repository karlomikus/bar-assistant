<?php

declare(strict_types=1);

namespace Kami\Cocktail\DataObjects\Cocktail;

class Substitute
{
    public function __construct(
        public readonly int $ingredientId,
        public readonly ?float $amount = null,
        public readonly ?string $units = null,
    ) {
    }
}
