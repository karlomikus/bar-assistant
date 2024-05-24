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

    public static function fromArray(array $source): self
    {
        return new self(
            $source['id'],
            ($source['amount'] ?? null) !== null ? (float) $source['amount'] : null,
            ($source['amount_max'] ?? null) !== null ? (float) $source['amount_max'] : null,
            $source['units'] ?? null,
        );
    }
}
