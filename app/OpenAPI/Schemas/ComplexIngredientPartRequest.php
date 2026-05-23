<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['ingredient_id', 'amount', 'units'])]
readonly class ComplexIngredientPartRequest
{
    public function __construct(
        #[OAT\Property(property: 'ingredient_id', example: 1)]
        public int $ingredientId,
        #[OAT\Property(example: 200.0)]
        public float $amount,
        #[OAT\Property(property: 'amount_max', example: null, nullable: true)]
        public ?float $amountMax = null,
        #[OAT\Property(example: 'ml')]
        public string $units = 'unit',
        #[OAT\Property(example: 'freshly squeezed', nullable: true)]
        public ?string $note = null,
    ) {
    }

    /**
     * @param array<string, mixed> $source
     */
    public static function fromArray(array $source): self
    {
        $ingredientId = is_numeric($source['ingredient_id'] ?? null) ? (int) $source['ingredient_id'] : 0;
        $amount = is_numeric($source['amount'] ?? null) ? (float) $source['amount'] : 0.0;
        $amountMax = is_numeric($source['amount_max'] ?? null) ? (float) $source['amount_max'] : null;
        $units = isset($source['units']) && is_string($source['units']) ? $source['units'] : 'unit';
        $note = isset($source['note']) && is_string($source['note']) ? $source['note'] : null;

        return new self($ingredientId, $amount, $amountMax, $units, $note);
    }
}
