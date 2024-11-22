<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\ValueObjects;

use Brick\Money\Money;

readonly class Price
{
    public function __construct(private Money $money)
    {
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function getPriceAsFloat(): float
    {
        return $this->money->getAmount()->toFloat();
    }

    public function getPriceAsMinor(): int
    {
        return $this->money->getMinorAmount()->toInt();
    }

    public function getFormattedPrice(): string
    {
        return (string) $this->money;
    }

    public function getCurrency(): string
    {
        return $this->money->getCurrency()->getCurrencyCode();
    }
}
