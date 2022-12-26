<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

class IngredientParser
{
    private array $units = [
        'oz' => ['oz.', 'ounce', 'fl-oz', 'oz', 'ounces'],
        'ml' => ['ml', 'ml.', 'milliliter', 'milliliters'],
        'cl' => ['cl', 'cl.', 'centiliter', 'centiliters'],
        'dash' => ['dashes', 'dash'],
        'sprigs' => ['sprig', 'sprigs'],
        'leaves' => ['leaves', 'leaf'],
        'whole' => ['whole'],
        'drops' => ['drop', 'drops'],
        'barspoon' => ['barspoon', 'teaspoon', 'tsp', 'tsp.', 'tspn', 't', 't.', 'teaspoon', 'teaspoons', 'tablespoons', 'tablespoon'],
        'slice' => ['slice', 'sliced', 'slices'],
        'cup' => ['c', 'c.', 'cup', 'cups'],
        'pint' => ['pt', 'pts', 'pt.', 'pint', 'pints'],
        'splash' => ['splash', 'splashes'],
        'pinch' => ['pinches', 'pinch'],
        'topup' => ['topup'],
    ];

    public function __construct(private readonly string $ingredientString)
    {
    }

    public function parse(): array
    {
        // Remove special chars
        $ingredientString = str_replace('*', '', $this->ingredientString);

        [$amount, $ingredientString] = $this->parseAmount($ingredientString);
        [$units, $ingredientString] = $this->parseUnit($ingredientString);
        [$name, $ingredientString] = $this->parseName($ingredientString);

        $units = $units === '' ? null : $units;

        // If we have a unit and no amount, guess it's 1 of unit
        if ($units !== null && $amount === 0) {
            $amount = 1;
        }

        return [
            'amount' => $amount,
            'units' => $units === '' ? null : $units,
            'name' => ucfirst(trim($name)),
        ];
    }

    private function parseAmount(string $ingredientString): array
    {
        if ($encString = iconv('', 'US//TRANSLIT', $ingredientString)) {
            $ingredientString = trim($encString);
        }

        // Check multiple spaces and replace with a single space
        $ingredientString = preg_replace('/[\s]+/', ' ', $ingredientString);

        // Default return value
        $amount = ['0', $ingredientString];

        // Match variable amounts (ex: 3-6 mint sprigs)
        $hasVariableAmount = preg_match('/^(\d+\-\d+)|^(\d+\s\-\s\d+)|^(\d+\s(to)\s\d+)/', $ingredientString, $varMatches);
        if ($hasVariableAmount === 1) {
            $amount = $varMatches[0];

            $restOfTheString = $ingredientString;
            if ($amount !== '') {
                $restOfTheString = explode($amount, $ingredientString)[1];
            }

            return [trim(explode('-', str_replace('to', '-', $amount))[0]), trim($restOfTheString)];
        }

        // Match specific amounts (ex: 30 ml ingredient, 1 1/2 oz ingredient)
        $hasSpecificAmount = preg_match('/^(\d+\/\d+)|(\d+\s\d+\/\d+)|(\d+.\d+)|\d+/', $ingredientString, $specMatches);
        if ($hasSpecificAmount === 1) {
            $amount = $specMatches[0];

            $restOfTheString = $ingredientString;
            if ($amount !== '') {
                $restOfTheString = explode($amount, $ingredientString)[1];
            }

            return [trim($amount), trim($restOfTheString)];
        }

        return $amount;
    }

    private function parseUnit(string $ingredientString): array
    {
        foreach($this->units as $unit => $alts) {
            foreach ($alts as $matchUnit) {
                // Match the whole word
                if (preg_match('/\b'. $matchUnit .'\b/i', $ingredientString) === 1) {
                    return [$unit, trim(preg_replace('/\b'. $matchUnit .'\b/i', '', $ingredientString), " \n\r\t\v\x00\.")];
                }
            }
        }

        return ['', $ingredientString];
    }

    private function parseName(string $ingredientString): array
    {
        // Remove everything between brackets
        $ingredientString = preg_replace('/\((.*?)\)/', '', $ingredientString);

        return [$ingredientString, $ingredientString];
    }
}
