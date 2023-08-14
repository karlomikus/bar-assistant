<?php

declare(strict_types=1);

namespace Kami\Cocktail\UnitConverter;

use Kami\RecipeUtils\RecipeIngredient;

class Converter
{
    public static function tryConvert(RecipeIngredient $recipeIngredient, Units $to): RecipeIngredient
    {
        $from = Units::tryFrom($recipeIngredient->units);
        if ($from === null) {
            return $recipeIngredient;
        }

        $converterClass = __NAMESPACE__ . '\\' . $from->name;
        $fromUnit = call_user_func($converterClass . '::fromString', $recipeIngredient->amount);
        $method = 'to' . $to->name;

        if (!method_exists($fromUnit, $method)) {
            return $recipeIngredient;
        }

        return new RecipeIngredient(
            $recipeIngredient->name,
            $fromUnit->{$method}()->getValue(),
            $to->value,
            $recipeIngredient->source
        );
    }
}
