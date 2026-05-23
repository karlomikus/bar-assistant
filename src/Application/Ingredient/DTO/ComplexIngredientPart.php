<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

final readonly class ComplexIngredientPart
{
    public function __construct(
        public int $ingredientId,
        public float $amount,
        public ?float $amountMax = null,
        public string $units = 'unit',
        public ?string $note = null,
    ) {
    }
}
