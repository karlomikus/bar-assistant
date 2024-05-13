<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use JsonSerializable;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Models\CocktailIngredient;

class AmountFormats implements JsonSerializable
{
    public function __construct(private readonly CocktailIngredient $cocktailIngredient, private readonly string $type = 'min')
    {
    }

    public function jsonSerialize(): mixed
    {
        $unitsToConvertTo = [Units::Ml, Units::Oz, Units::Cl];
        $formats = [];
        $orgAmount = $this->type === 'min' ? $this->cocktailIngredient->amount : $this->cocktailIngredient->amount_max;

        foreach ($unitsToConvertTo as $unitTo) {
            $amount = $this->type === 'min' ? $this->cocktailIngredient->getAmountAsUnit($unitTo) : $this->cocktailIngredient->getMaxAmountAsUnit($unitTo);

            $formats[$unitTo->value] = $amount ? [
                'value' => $amount,
                'units' => $unitTo->value,
                'text' => sprintf('%s %s', $amount, $unitTo->value),
            ] : null;
        }

        return array_merge([
            'original' => [
                'value' => $orgAmount,
                'units' => $this->cocktailIngredient->units,
                'text' => sprintf('%s %s', $orgAmount, $this->cocktailIngredient->units),
            ]
        ], $formats);
    }
}
