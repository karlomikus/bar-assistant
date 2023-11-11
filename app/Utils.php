<?php

declare(strict_types=1);

namespace Kami\Cocktail;

class Utils
{
    /**
     * Return an array of amount and units, converts to "ml" when possible
     *
     * @param string $ingredientString String in "{amount} {units}" format, ex: 1 oz
     * @return array{amount: int|float, units: string}
     */
    public static function parseIngredientAmount(string $ingredientString): array
    {
        // Get rid of unicode fraction display
        if ($encString = iconv('', 'US//TRANSLIT', $ingredientString)) {
            $ingredientString = trim($encString);
        }

        $convert = function (string $string): float {
            $amountSplit = explode('/', $string);

            return ((int) $amountSplit[0] / (int) ($amountSplit[1] ?? 1)) * 30;
        };

        if ($splString = preg_split("/[\s]+/", $ingredientString, flags: PREG_SPLIT_NO_EMPTY)) {
            $units = end($splString);

            if (strtolower($units) === 'oz') {
                if (count($splString) === 3) {
                    $amount = (int) $splString[0] * 30;
                    $amount += $convert($splString[1]);
                } else {
                    $amount = $convert($splString[0]);
                }
                $units = 'ml';
            } elseif (strtolower($units) === 'cl') {
                $amount = str_replace(',', '.', $splString[0]);
                $amount = (int) (floatval($amount) * 10);
                $units = 'ml';
            } else {
                $amount = $splString[0];
                $amount = str_replace(',', '.', $amount);
            }
        } else {
            $amount = '0';
            $units = 'ml';
        }

        if (floor(floatval($amount)) == $amount) {
            $amount = intval($amount);
        } else {
            $amount = floatval($amount);
        }

        return [
            'amount' => $amount,
            'units' => $units,
        ];
    }

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

    public static function cleanSpaces(string $str): string
    {
        $str = mb_convert_encoding($str, 'UTF-8', mb_detect_encoding($str));
        $str = str_replace("&nbsp;", " ", $str);
        $str = str_replace(" ", " ", $str);

        return $str;
    }
}
