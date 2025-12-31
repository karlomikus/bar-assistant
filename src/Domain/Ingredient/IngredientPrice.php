<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Support\AmountWithUnits;
use BarAssistant\Domain\Support\Price;
use BarAssistant\Domain\Support\Unit;

final readonly class IngredientPrice
{
    public function __construct(
        private PriceCategoryId $priceCategoryId,
        private Price $price,
        private AmountWithUnits $amount,
        private ?string $description = null,
    ) {
    }

    public static function create(PriceCategoryId $priceCategoryId, int $price, string $currency, float $amount, string $units, ?string $description = null): self
    {
        return new self(
            priceCategoryId: $priceCategoryId,
            price: Price::createFromMinor($price, $currency),
            amount: new AmountWithUnits($amount, new Unit($units)),
            description: $description,
        );
    }

    public function getPriceCategoryId(): PriceCategoryId
    {
        return $this->priceCategoryId;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getAmount(): AmountWithUnits
    {
        return $this->amount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
