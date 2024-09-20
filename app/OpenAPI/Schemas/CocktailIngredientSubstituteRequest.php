<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['ingredient_id'])]
readonly class CocktailIngredientSubstituteRequest
{
    public function __construct(
        #[OAT\Property(property: 'ingredient_id')]
        public int $ingredientId,
        #[OAT\Property(example: 30)]
        public ?float $amount = null,
        #[OAT\Property(property: 'amount_max', example: 60)]
        public ?float $amountMax = null,
        #[OAT\Property(example: 'ml')]
        public ?string $units = null,
    ) {
    }

    /**
     * @param array<mixed> $source
     */
    public static function fromArray(array $source): self
    {
        return new self(
            (int) $source['ingredient_id'],
            ($source['amount'] ?? null) !== null ? (float) $source['amount'] : null,
            ($source['amount_max'] ?? null) !== null ? (float) $source['amount_max'] : null,
            $source['units'] ?? null,
        );
    }
}
