<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use DomainException;
use BarAssistant\Domain\Common\Price;
use BarAssistant\Domain\Common\AmountWithUnits;

final readonly class IngredientPrice
{
    private function __construct(
        private PriceCategoryId $priceCategoryId,
        private Price $price,
        private AmountWithUnits $amountWithUnits,
        private ?string $description = null,
    ) {
        if ($price->getAsMinor() <= 0) {
            throw new DomainException('Price must be greater than zero');
        }
    }

    public static function create(
        PriceCategoryId $priceCategoryId,
        Price $price,
        AmountWithUnits $amountWithUnits,
        ?string $description = null,
    ): self {
        return new self(
            priceCategoryId: $priceCategoryId,
            price: $price,
            amountWithUnits: $amountWithUnits,
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

    public function getAmountWithUnits(): AmountWithUnits
    {
        return $this->amountWithUnits;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
