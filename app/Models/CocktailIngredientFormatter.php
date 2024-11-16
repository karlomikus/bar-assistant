<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Kami\RecipeUtils\Converter;
use Kami\RecipeUtils\UnitConverter\Units;

final readonly class CocktailIngredientFormatter
{
    /**
     * @param array<Units> $ignoreUnits
     */
    public function __construct(
        private CocktailIngredient|CocktailIngredientSubstitute $cocktailIngredient,
        private ?Units $toUnits = null,
        private array $ignoreUnits = [Units::Dash],
    ) {
    }

    public function getOriginalUnitsAsEnum(): ?Units
    {
        if (!$this->cocktailIngredient->units) {
            return null;
        }

        return Units::tryFrom($this->cocktailIngredient->units);
    }

    public function getOriginalAmount(): ?float
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

    public function getUnits(): ?string
    {
        $currentUnits = $this->getOriginalUnitsAsEnum();
        if ($this->toUnits === null || !$currentUnits || in_array($currentUnits, $this->ignoreUnits, true)) {
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

        $optional = '';
        if ($this->cocktailIngredient instanceof CocktailIngredient) {
            $optional = $this->cocktailIngredient->optional === true ? ' (optional)' : '';
        }

        return trim(sprintf('%s %s%s', $this->printAmounts(), $name, $optional));
    }

    private function convert(?float $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if ($this->getOriginalUnitsAsEnum()) {
            if (in_array($this->getOriginalUnitsAsEnum(), $this->ignoreUnits, true) || $this->toUnits === null) {
                return $value;
            }

            return Converter::fromTo($value, $this->getOriginalUnitsAsEnum(), $this->toUnits);
        }

        return $value;
    }
}
