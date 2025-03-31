<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Repository\IngredientRepository;

final class BarOptimizerService
{
    public function __construct(
        private readonly IngredientRepository $ingredientRepository,
    ) {
    }

    public function optimize(int $barId): void
    {
        Log::info('[' . $barId . '] Starting bar optimization for bar ID: ' . $barId);

        $this->ingredientRepository->rebuildMaterializedPath($barId);
        Log::info('[' . $barId . '] Finished rebuilding materialized path for ingredients.');

        Cocktail::where('bar_id', $barId)->with('ingredients.ingredient')->chunk(50, function ($cocktails) {
            foreach ($cocktails as $cocktail) {
                $calculatedAbv = $cocktail->getABV();
                $cocktail->abv = $calculatedAbv;
                $cocktail->save();
            }
        });
        Log::info('[' . $barId . '] Finished updating cocktail ABVs.');

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
            DB::table('ingredients')
                ->where('id', $commonUnit->ingredient_id)
                ->where('bar_id', $barId)
                ->whereNull('units')
                ->update(['units' => $commonUnit->units]);
        }
        Log::info('[' . $barId . '] Finished updating ingredient default units.');

        /** @phpstan-ignore-next-line */
        Cocktail::where('bar_id', $barId)->searchable();
        /** @phpstan-ignore-next-line */
        Ingredient::where('bar_id', $barId)->searchable();

        Log::info('[' . $barId . '] Finished re-indexing cocktails and ingredients.');
    }
}
