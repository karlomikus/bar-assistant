<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

use Brick\Math\RoundingMode;
use Brick\Money\Money;

final readonly class Price
{
    private function __construct(private Money $money)
    {
    }

    public static function createFromMinor(int $amountInMinor, string $currency): self
    {
        return new self(Money::ofMinor($amountInMinor, $currency));
    }

    public static function createFromFloat(float $decimalAmount, string $currency): self
    {
        return new self(Money::of($decimalAmount, $currency, roundingMode: RoundingMode::UP));
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
