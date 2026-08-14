<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\ValueObjects;

use Brick\Money\Money;

readonly class Price
{
    private function __construct(private Money $money)
    {
    }

    public static function fromMinor(int $minorPrice, string $currency): self
    {
        return new self(
            Money::ofMinor($minorPrice, $currency),
        );
    }

    public static function fromMoney(Money $money): self
    {
        return new self($money);
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
