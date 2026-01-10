<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

final readonly class CreateIngredientPrice
{
    public function __construct(
        public int $priceCategoryId,
        public float $price,
        public float $amount,
        public string $units,
        public ?string $description = null,
    ) {
    }
}
