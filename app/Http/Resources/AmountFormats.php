<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use JsonSerializable;
use Kami\Cocktail\Models\UnitValueObject;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Models\CocktailIngredientFormatter;

final class AmountFormats implements JsonSerializable
{
    public function __construct(private readonly CocktailIngredient $cocktailIngredient)
    {
    }

    public function jsonSerialize(): mixed
    {
        $unitsToConvertTo = ['ml', 'oz', 'cl'];
        $formats = [];

        foreach ($unitsToConvertTo as $unitTo) {
            $convertedAmount = $this->cocktailIngredient->getAmount()->convertTo(new UnitValueObject($unitTo));

            $formats[$unitTo] = [
                'amount' => $convertedAmount->amountMin,
                'amount_max' => $convertedAmount->amountMax,
                'units' => $convertedAmount->units->value,
                'full_text' => (new CocktailIngredientFormatter($convertedAmount, $this->cocktailIngredient->ingredient->name, $this->cocktailIngredient->optional))->format(),
            ];
        }

        return $formats;
    }
}
