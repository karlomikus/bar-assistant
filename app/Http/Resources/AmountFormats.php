<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use JsonSerializable;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Models\CocktailIngredient;

final class AmountFormats implements JsonSerializable
{
    public function __construct(private readonly CocktailIngredient $cocktailIngredient)
    {
    }

    public function jsonSerialize(): mixed
    {
        $unitsToConvertTo = [Units::Ml, Units::Oz, Units::Cl];
        $formats = [];

        foreach ($unitsToConvertTo as $unitTo) {
            $convertedModel = $this->cocktailIngredient->getConvertedTo($unitTo);

            $formats[$unitTo->value] = [
                'amount' => $convertedModel->getAmount(),
                'amount_max' => $convertedModel->getMaxAmount(),
                'units' => $convertedModel->getUnits(),
                'full_text' => $convertedModel->printIngredient(),
            ];
        }

        return $formats;
    }
}
