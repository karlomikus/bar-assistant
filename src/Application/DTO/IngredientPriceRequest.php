<?php

declare(strict_types=1);

namespace BarAssistant\Application\DTO;

final readonly class IngredientPriceRequest
{
    public function __construct(
        public int $priceCategoryId,
        public int $price,
        public float $amount,
        public string $units,
        public ?string $description = null,
    ) {
    }
}
