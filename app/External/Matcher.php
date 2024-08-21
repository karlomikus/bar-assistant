<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

use Kami\Cocktail\Models\Glass;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\CocktailMethod;
use Kami\Cocktail\Services\IngredientService;
use Kami\Cocktail\Models\Ingredient as IngredientModel;
use Kami\Cocktail\DTO\Ingredient\Ingredient as IngredientDTO;

class Matcher
{
    /** @var array<string, int> */
    private array $matchedIngredients = [];

    /** @var array<string, int> */
    private array $matchedCocktails = [];

    /** @var array<string, int> */
    private array $matchedGlasses = [];

    /** @var array<string, int> */
    private array $matchedMethods = [];

    public function __construct(private readonly int $barId, private readonly IngredientService $ingredientService)
    {
    }

    public function matchCocktailByName(string $name): ?int
    {
        $matchName = mb_strtolower($name, 'UTF-8');

        if (isset($this->matchedCocktails[$matchName])) {
            return $this->matchedCocktails[$matchName];
        }

        $this->matchedCocktails = DB::table('cocktails')->select('id', 'name')->where('bar_id', $this->barId)->get()->map(function ($row) {
            $row->name = mb_strtolower($row->name, 'UTF-8');

            return $row;
        })->pluck('id', 'name')->toArray();

        $existingCocktail = $this->matchedCocktails[$matchName] ?? null;
        if ($existingCocktail) {
            $this->matchedCocktails[$matchName] = $existingCocktail;

            return $existingCocktail;
        }

        return null;
    }

    public function matchOrCreateIngredientByName(IngredientDTO $ingredient): int
    {
        $matchName = mb_strtolower($ingredient->name, 'UTF-8');

        if (isset($this->matchedIngredients[$matchName])) {
            return $this->matchedIngredients[$matchName];
        }

        $this->matchedIngredients = DB::table('ingredients')->select('id', 'name')->where('bar_id', $this->barId)->get()->map(function ($row) {
            $row->name = mb_strtolower($row->name, 'UTF-8');

            return $row;
        })->pluck('id', 'name')->toArray();

        $existingIngredient = $this->matchedIngredients[$matchName] ?? null;
        if ($existingIngredient) {
            $this->matchedIngredients[$matchName] = $existingIngredient;

            return $existingIngredient;
        }

        $newIngredient = $this->ingredientService->createIngredient($ingredient);

        $this->matchedIngredients[$matchName] = $newIngredient->id;

        return $newIngredient->id;
    }

    public function matchGlassByName(string $name): ?int
    {
        $matchName = mb_strtolower($name, 'UTF-8');

        if (isset($this->matchedGlasses[$matchName])) {
            return $this->matchedGlasses[$matchName];
        }

        $this->matchedGlasses = DB::table('glasses')->select('id', 'name')->where('bar_id', $this->barId)->get()->map(function ($row) {
            $row->name = mb_strtolower($row->name, 'UTF-8');

            return $row;
        })->pluck('id', 'name')->toArray();

        $existingGlass = $this->matchedGlasses[$matchName] ?? null;
        if ($existingGlass) {
            $this->matchedGlasses[$matchName] = $existingGlass;

            return $existingGlass;
        }

        return null;
    }

    public function matchMethodByName(string $name): ?int
    {
        $matchName = mb_strtolower($name, 'UTF-8');

        if (isset($this->matchedMethods[$matchName])) {
            return $this->matchedMethods[$matchName];
        }

        $this->matchedMethods = DB::table('cocktail_methods')->select('id', 'name')->where('bar_id', $this->barId)->get()->map(function ($row) {
            $row->name = mb_strtolower($row->name, 'UTF-8');

            return $row;
        })->pluck('id', 'name')->toArray();

        $existingMethod = $this->matchedMethods[$matchName] ?? null;
        if ($existingMethod) {
            $this->matchedMethods[$matchName] = $existingMethod;

            return $existingMethod;
        }

        return null;
    }
}
