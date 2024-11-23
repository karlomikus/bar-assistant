<?php

declare(strict_types=1);

namespace Kami\Cocktail;

use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Models\ValueObjects\AmountValueObject;
use Kami\Cocktail\Models\ValueObjects\UnitValueObject;

final class Utils
{
    /**
     * Calculate approximate cocktail ABV
     * Formula based on https://jeffreymorgenthaler.com/
     *
     * @param array<array{amount:float|int, strength: float|int}> $ingredients
     * @param int $dilutionPercentage
     * @return float
     */
    public static function calculateAbv(array $ingredients, int $dilutionPercentage): float
    {
        $dilutionPercentage = $dilutionPercentage / 100;

        $amountUsed = array_sum(array_column($ingredients, 'amount'));
        $alcoholVolume = floatval(array_reduce($ingredients, function ($carry, $item) {
            return (($item['amount'] * $item['strength']) / 100) + $carry;
        }));

        $afterDilution = ($amountUsed * $dilutionPercentage) + $amountUsed;

        if ($afterDilution <= 0) {
            return 0.0;
        }

        return round(($alcoholVolume / $afterDilution) * 100, 2);
    }

    /**
     * Calculate approximate cocktail volume
     *
     * @param array<AmountValueObject> $ingredients
     * @return float
     */
    public static function calculateVolume(array $ingredients, Units $inUnits = Units::Ml): float
    {
        // Convert all amounts to single unit
        $ingredients = array_map(function ($ingredient) {
            if ($ingredient->units->value === 'ml') {
                return $ingredient;
            }

            if ($ingredient->units->value === 'cl' || $ingredient->units->value === 'oz' || $ingredient->units->isDash() || $ingredient->units->isBarspoon()) {
                return $ingredient->convertTo(new UnitValueObject('ml'));
            }

            if ($ingredient->units->isTopup()) {
                return new AmountValueObject(90, new UnitValueObject('ml'));
            }
        }, $ingredients);

        $volume = array_reduce(array_filter($ingredients), fn ($carry, $item) => $carry + $item->amountMin, 0.0);
        $volume = match ($inUnits) {
            Units::Ml => $volume,
            Units::Cl => $volume / 10,
            Units::Oz => $volume / 30,
            default => 0.0,
        };

        return round($volume, 2);
    }
}
