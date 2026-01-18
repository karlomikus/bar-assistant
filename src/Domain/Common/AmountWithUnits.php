<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

use Stringable;
use Kami\RecipeUtils\Converter;
use Kami\RecipeUtils\AmountValue;

final readonly class AmountWithUnits implements Stringable
{
    private function __construct(
        public float $amountMin,
        public Unit $units,
        public ?float $amountMax = null,
    ) {
    }

    public static function from(float $amountMin, Unit $units, ?float $amountMax = null): self
    {
        return new self($amountMin, $units, $amountMax);
    }

    public function convertTo(Unit $toUnits): self
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

        return new self(round($convertedMinAmount->getValue(), 4), $toUnits, $convertedMaxAmount?->getValue());
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
