<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Support\AmountWithUnits;
use BarAssistant\Domain\Support\Price;

final readonly class IngredientPrice
{
    public function __construct(
        private PriceCategoryId $priceCategoryId,
        private Price $price,
        private AmountWithUnits $amountWithUnits,
        private ?string $description = null,
    ) {
    }

    public function getPriceCategoryId(): PriceCategoryId
    {
        return $this->priceCategoryId;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getAmountWithUnits(): AmountWithUnits
    {
        return $this->amountWithUnits;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
