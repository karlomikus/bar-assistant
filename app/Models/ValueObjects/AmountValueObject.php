<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\ValueObjects;

use Stringable;
use Kami\RecipeUtils\Converter;
use Kami\RecipeUtils\AmountValue;

final readonly class AmountValueObject implements Stringable
{
    public function __construct(
        public float $amountMin,
        public UnitValueObject $units,
        public ?float $amountMax = null,
    ) {
    }

    public function convertTo(UnitValueObject $toUnits): self
    {
        $fromUnitsEnum = $this->units->getAsEnum();
        $toUnitsEnum = $toUnits->getAsEnum();

        if (!$fromUnitsEnum || !$toUnitsEnum) {
            return new self($this->amountMin, $this->units, $this->amountMax);
        }

        $convertedMinAmount = Converter::convertAmount(AmountValue::from($this->amountMin), $fromUnitsEnum, $toUnitsEnum);
        $convertedMaxAmount = null;
        if ($this->amountMax) {
            $convertedMaxAmount = Converter::convertAmount(AmountValue::from($this->amountMax), $fromUnitsEnum, $toUnitsEnum);
        }

        return new self($convertedMinAmount->getValue(), $toUnits, $convertedMaxAmount?->getValue());
    }

    public function __toString(): string
    {
        $amountMin = $this->amountMin;
        $amountMax = $this->amountMax;
        $units = $this->units->value;

        $str = sprintf('%s %s', $amountMin, $units);
        if ($amountMax) {
            $str .= sprintf(' - %s %s', $amountMax, $units);
        }

        return $str;
    }

    /**
     * @return array<string, float|string|null>
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amountMin,
            'units' => $this->units->value,
            'amount_max' => $this->amountMax,
        ];
    }
}
