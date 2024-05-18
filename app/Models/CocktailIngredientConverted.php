<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Kami\RecipeUtils\Converter;
use Kami\RecipeUtils\UnitConverter\Units;

class CocktailIngredientConverted
{
    public function __construct(private readonly CocktailIngredient $cocktailIngredient, private readonly Units $toUnits)
    {
    }

    public function getOriginalUnitsAsEnum(): ?Units
    {
        return Units::tryFrom($this->cocktailIngredient->units);
    }

    public function getOriginalAmount(): float
    {
        return $this->cocktailIngredient->amount;
    }

    public function getOriginalMaxAmount(): ?float
    {
        return $this->cocktailIngredient->amount_max;
    }

    public function getAmount(): ?float
    {
        return $this->convert($this->cocktailIngredient->amount);
    }

    public function getMaxAmount(): ?float
    {
        return $this->convert($this->cocktailIngredient->amount_max);
    }

    public function getUnits(): string
    {
        $currentUnits = $this->getOriginalUnitsAsEnum();
        if (!$currentUnits || $currentUnits === Units::Dash) {
            return $this->cocktailIngredient->units;
        }

        return $this->toUnits->value;
    }

    public function printAmounts(): string
    {
        $amountMin = $this->getAmount();
        $amountMax = $this->getMaxAmount();
        $units = $this->getUnits();

        $str = sprintf('%s %s', $amountMin, $units);
        if ($amountMax) {
            $str .= sprintf(' - %s %s', $amountMax, $units);
        }

        return $str;
    }

    public function printIngredient(): string
    {
        $name = $this->cocktailIngredient->ingredient->name;
        $optional = $this->cocktailIngredient->optional === true ? ' (optional)' : '';

        return sprintf('%s %s%s', $this->printAmounts(), $name, $optional);
    }

    private function convert(?float $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if ($this->getOriginalUnitsAsEnum()) {
            if ($this->getOriginalUnitsAsEnum() === Units::Dash) {
                return $value;
            }

            return Converter::fromTo($value, $this->getOriginalUnitsAsEnum(), $this->toUnits);
        }

        return $value;
    }
}
