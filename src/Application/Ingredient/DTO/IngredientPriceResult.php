<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

final readonly class IngredientPriceResult
{
    public function __construct(
        public int $ingredientPriceId,
        public int $price,
        public float $amount,
        public string $currency,
        public string $units,
        public ?string $description = null,
    ) {
    }
}
