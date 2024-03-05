<?php

declare(strict_types=1);

namespace Kami\Cocktail\DTO\Cocktail;

readonly class Substitute
{
    public function __construct(
        public int $ingredientId,
        public ?float $amount = null,
        public ?float $amountMax = null,
        public ?string $units = null,
    ) {
    }
}
