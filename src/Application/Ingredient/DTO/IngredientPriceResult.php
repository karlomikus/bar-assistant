<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

use BarAssistant\Domain\Ingredient\IngredientPrice;

final readonly class IngredientPriceResult
{
    public function __construct(
        public int $priceCategoryId,
        public int $price,
        public float $amount,
        public string $currency,
        public string $units,
        public ?string $description = null,
    ) {
    }

    public static function fromIngredientPrice(IngredientPrice $price): IngredientPriceResult
    {
        return new IngredientPriceResult(
            priceCategoryId: $price->getPriceCategoryId()->value,
            price: $price->getPrice()->getAsMinor(),
            amount: $price->getAmountWithUnits()->amountMin,
            currency: $price->getPrice()->getCurrency(),
            units: $price->getAmountWithUnits()->units->value,
            description: $price->getDescription(),
        );
    }
}
