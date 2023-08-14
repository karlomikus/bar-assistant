<?php

declare(strict_types=1);

namespace Kami\Cocktail\UnitConverter;

class Unit
{
    final public function __construct(private readonly float $value)
    {
    }

    public function getValue(): float
    {
        return round($this->value, 2);
    }

    public static function from(float $value): static
    {
        return new static($value);
    }

    /**
     * Try to parse string representation of number to a float
     * Takes in account only the most usual cocktail unit representation
     * Not made for accuracy
     *
     * For example:
     * "1/2" oz -> 0.5 oz
     * "1 1/2" oz -> 1.5 oz
     * "¾" oz -> 0.75 oz
     *
     * @param string $value
     * @return static
     */
    public static function fromString(string $value): static
    {
        // Remove unicode fraction display
        if ($encString = iconv('', 'US//TRANSLIT', $value)) {
            $value = trim($encString);
        }

        // String is not fractional display (1/2 or 1 2/3), just cast to float
        if (!str_contains($value, '/') && !str_contains($value, ' ')) {
            $value = str_replace(',', '.', $value);

            return new static((float) $value);
        }

        // Solve fractional display
        $solveFraction = function (string $string): float {
            $amountSplit = explode('/', $string);

            return (int) $amountSplit[0] / (int) ($amountSplit[1] ?? 1);
        };

        $amount = 0.0;

        if ($splString = preg_split("/[\s]+/", $value, flags: PREG_SPLIT_NO_EMPTY)) {
            // Match whole number with fractional (example: "1 1/2")
            if (count($splString) === 2) {
                $amount = (float) $splString[0];
                $amount += $solveFraction($splString[1]);
            } else {
                $amount = $solveFraction($splString[0]);
            }
        }

        return new static((float) $amount);
    }
}
