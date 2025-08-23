<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Kami\Cocktail\Models\Bar;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\Models\Ingredient;

final class BarOptimizerService
{
    public function __construct(
        private readonly IngredientService $ingredientRepository,
    ) {
    }

    public function optimize(int $barId): void
    {
        $bar = Bar::findOrFail($barId);
        $barSettings = $bar->settings ?? [];
        $forceToUnits = $barSettings['default_units'] ?? null;

        Log::info('[' . $barId . '] Starting bar optimization for bar ID: ' . $barId);

        $this->ingredientRepository->rebuildMaterializedPath($barId);
        Log::info('[' . $barId . '] Finished rebuilding materialized path for ingredients.');

        // Cocktail::where('bar_id', $barId)->with('ingredients.ingredient')->chunk(50, function ($cocktails) {
        //     foreach ($cocktails as $cocktail) {
        //         $calculatedAbv = $cocktail->getABV();
        //         $cocktail->abv = $calculatedAbv;
        //         $cocktail->save();
        //     }
        // });
        // Log::info('[' . $barId . '] Finished updating cocktail ABVs.');

        // Find the most used unit per ingredient
        $unitsPerIngredient = DB::table('cocktail_ingredients')
            ->select(
                'cocktail_ingredients.ingredient_id',
                'cocktail_ingredients.units',
                DB::raw('COUNT(cocktail_ingredients.ingredient_id) AS count')
            )
            ->join('ingredients', 'cocktail_ingredients.ingredient_id', '=', 'ingredients.id')
            ->where('ingredients.bar_id', $barId)
            ->orderBy('cocktail_ingredients.ingredient_id')
            ->orderBy('count', 'desc')
            ->groupBy('cocktail_ingredients.ingredient_id', 'cocktail_ingredients.units')
            ->get()
            ->unique('ingredient_id')
            ->values();

        foreach ($unitsPerIngredient as $commonUnit) {
            $ingredientUnits = $commonUnit->units;

            // Force unit conversion if specified and the unit is convertible
            if ($forceToUnits && $forceToUnits !== $ingredientUnits) {
                $convertableUnits = ['ml', 'oz', 'cl'];
                if (in_array($ingredientUnits, $convertableUnits) && in_array($forceToUnits, $convertableUnits)) {
                    $ingredientUnits = $forceToUnits;
                    Log::debug('[' . $barId . '] Forcing unit conversion from ' . $commonUnit->units . ' to ' . $forceToUnits . ' for ingredient ID: ' . $commonUnit->ingredient_id);
                }
            }

            DB::table('ingredients')
                ->where('id', $commonUnit->ingredient_id)
                ->where('bar_id', $barId)
                ->whereNull('units')
                ->update(['units' => $ingredientUnits]);
        }
        Log::info('[' . $barId . '] Finished updating ingredient default units.');

        if (!empty(config('scout.driver'))) {
            /** @phpstan-ignore-next-line */
            Cocktail::where('bar_id', $barId)->searchable();
            /** @phpstan-ignore-next-line */
            Ingredient::where('bar_id', $barId)->searchable();
        }

        Log::info('[' . $barId . '] Finished re-indexing cocktails and ingredients.');
    }
}
