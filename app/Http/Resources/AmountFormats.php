<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use JsonSerializable;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Models\ValueObjects\UnitValueObject;
use Kami\Cocktail\Models\ValueObjects\CocktailIngredientFormatter;

#[OAT\Schema(
    schema: 'AmountFormats',
    description: 'Represents the amount of cocktail ingredient in different formats',
    properties: [
        new OAT\Property('ml', type: 'object', required: ['amount', 'amount_max', 'units', 'full_text'], properties: [
            new OAT\Property('amount', type: 'number', format: 'float', example: 30),
            new OAT\Property('amount_max', type: 'number', format: 'float', example: 60),
            new OAT\Property('units', type: 'string', example: 'ml'),
            new OAT\Property('full_text', type: 'string', example: '30-60 ml'),
        ]),
        new OAT\Property('oz', type: 'object', required: ['amount', 'amount_max', 'units', 'full_text'], properties: [
            new OAT\Property('amount', type: 'number', format: 'float', example: 1),
            new OAT\Property('amount_max', type: 'number', format: 'float', example: 2),
            new OAT\Property('units', type: 'string', example: 'oz'),
            new OAT\Property('full_text', type: 'string', example: '1-2 oz'),
        ]),
        new OAT\Property('cl', type: 'object', required: ['amount', 'amount_max', 'units', 'full_text'], properties: [
            new OAT\Property('amount', type: 'number', format: 'float', example: 3),
            new OAT\Property('amount_max', type: 'number', format: 'float', example: 6),
            new OAT\Property('units', type: 'string', example: 'cl'),
            new OAT\Property('full_text', type: 'string', example: '3-6 cl'),
        ]),
    ],
    required: ['ml', 'oz', 'cl']
)]
final readonly class AmountFormats implements JsonSerializable
{
    public function __construct(private CocktailIngredient $cocktailIngredient)
    {
    }

    public function jsonSerialize(): mixed
    {
        $unitsToConvertTo = UnitValueObject::CONVERTABLE_UNITS;
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
