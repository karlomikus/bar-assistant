<?php

declare(strict_types=1);

namespace Kami\Cocktail;

use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Models\ValueObjects\UnitValueObject;
use Kami\Cocktail\Models\ValueObjects\AmountValueObject;

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

        $volume = array_reduce(array_filter($ingredients), fn($carry, $item) => $carry + $item->amountMin, 0.0);
        $volume = match ($inUnits) {
            Units::Ml => $volume,
            Units::Cl => $volume / 10,
            Units::Oz => $volume / 30,
            default => 0.0,
        };

        return round($volume, 2);
    }

    /**
     * Parse a JSON file and return parsed data or default value if the file is invalid.
     *
     * @template T
     * @param string $path Path to the JSON file
     * @param mixed $default Default value to return if the file doesn't exist or is invalid
     * @param callable|null $mapper Optional mapper function to transform parsed data
     * @return mixed|T|array<T> Parsed data (single object, array of objects, or default value)
     */
    public static function parseJsonFile(string $path, mixed $default = [], ?callable $mapper = null): mixed
    {
        if (!file_exists($path)) {
            return $default;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return $default;
        }

        $json = json_decode($content, true);

        if ($json === null) {
            return $default;
        }

        if (is_array($json) && !isset($json[0])) {
            return $mapper ? $mapper($json) : $json;
        }

        if (is_array($json)) {
            return $mapper ? array_map($mapper, $json) : $json;
        }

        return $default;
    }
}
