<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Stringable;
use Kami\RecipeUtils\Converter;

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

        $convertedMinAmount = Converter::fromTo($this->amountMin, $fromUnitsEnum, $toUnitsEnum);
        $convertedMaxAmount = null;
        if ($this->amountMax) {
            $convertedMaxAmount = Converter::fromTo($this->amountMax, $fromUnitsEnum, $toUnitsEnum);
        }

        return new self($convertedMinAmount, $toUnits, $convertedMaxAmount);
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
}