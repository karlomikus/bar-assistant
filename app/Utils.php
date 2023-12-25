<?php

declare(strict_types=1);

namespace Kami\Cocktail;

class Utils
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
}
