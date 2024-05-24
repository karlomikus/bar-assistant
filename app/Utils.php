<?php

declare(strict_types=1);

namespace Kami\Cocktail;

use Kami\RecipeUtils\UnitConverter\Units;

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
     * @param array<array{amount:float|int, units: string}> $ingredients
     * @return float
     */
    public static function calculateVolume(array $ingredients, Units $inUnits = Units::Ml): float
    {
        // Convert all amounts to single unit
        $ingredients = array_map(function ($ingredient) {
            if ($ingredient['units'] === 'ml') {
                return $ingredient;
            }

            if ($ingredient['units'] === 'oz') {
                $ingredient['amount'] = $ingredient['amount'] * 30;
                $ingredient['units'] = 'ml';

                return $ingredient;
            }

            if ($ingredient['units'] === 'cl') {
                $ingredient['amount'] = $ingredient['amount'] * 10;
                $ingredient['units'] = 'ml';

                return $ingredient;
            }

            if (str_starts_with($ingredient['units'], 'dash') || str_starts_with($ingredient['units'], 'drop')) {
                $ingredient['amount'] = $ingredient['amount'] * 0.3125;
                $ingredient['units'] = 'ml';

                return $ingredient;
            }

            if (str_contains($ingredient['units'], 'spoon')) {
                $ingredient['amount'] = $ingredient['amount'] * 5;
                $ingredient['units'] = 'ml';

                return $ingredient;
            }

            if (str_starts_with($ingredient['units'], 'topup')) {
                $ingredient['amount'] = 90;
                $ingredient['units'] = 'ml';

                return $ingredient;
            }
        }, $ingredients);

        $volume = array_sum(array_column($ingredients, 'amount'));
        $volume = match ($inUnits) {
            Units::Ml => $volume,
            Units::Cl => $volume / 10,
            Units::Oz => $volume / 30,
            default => 0.0,
        };

        return round($volume, 2);
    }
}
