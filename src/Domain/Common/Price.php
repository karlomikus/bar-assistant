<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

use Brick\Money\Money;
use Brick\Math\RoundingMode;

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

    public function getAsFloat(): float
    {
        return $this->money->getAmount()->toFloat();
    }

    public function getAsMinor(): int
    {
        return $this->money->getMinorAmount()->toInt();
    }

    public function getFormatted(): string
    {
        return (string) $this->money;
    }

    public function getCurrency(): string
    {
        return $this->money->getCurrency()->getCurrencyCode();
    }
}
