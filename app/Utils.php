<?php

declare(strict_types=1);

namespace Kami\Cocktail;

use BarAssistant\Domain\Common\Unit;
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
        $alcoholVolume = floatval(array_reduce($ingredients, fn ($carry, $item) => (($item['amount'] * $item['strength']) / 100) + $carry));

        $afterDilution = ($amountUsed * $dilutionPercentage) + $amountUsed;

        if ($afterDilution <= 0) {
            return 0.0;
        }

        return round(($alcoholVolume / $afterDilution) * 100, 2);
    }

    /**
     * Convert an ingredient amount to milliliters.
     *
     * Top-ups have no meaningful amount and use a fixed volume instead.
     * Returns null when the unit cannot be converted to a volume.
     */
    public static function getVolumeInMl(AmountValueObject $ingredient): ?float
    {
        if ($ingredient->units->isTopup()) {
            return Unit::TOPUP_VOLUME_ML;
        }

        if ($ingredient->units->getAsEnum() === null) {
            return null;
        }

        return $ingredient->convertTo(new UnitValueObject('ml'))->amountMin;
    }

    /**
     * Calculate approximate cocktail volume
     *
     * @param array<AmountValueObject> $ingredients
     * @param float $dilutionPercentage Percentage of volume added through dilution
     * @return float
     */
    public static function calculateVolume(array $ingredients, Units $inUnits = Units::Ml, float $dilutionPercentage = 0.0): float
    {
        $volume = 0.0;
        foreach ($ingredients as $ingredient) {
            $volumeInMl = self::getVolumeInMl($ingredient);

            if ($volumeInMl === null) {
                continue;
            }

            $volume += $volumeInMl;
        }

        $volume = $volume * (1 + ($dilutionPercentage / 100));
        $volume = match ($inUnits) {
            Units::Ml => $volume,
            Units::Cl => $volume / 10,
            Units::Oz => $volume / 30,
            default => 0.0,
        };

        return round($volume, 2);
    }
}
