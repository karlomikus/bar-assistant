<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\CocktailMethod;
use Kami\Cocktail\Services\IngredientService;
use Kami\Cocktail\Models\Ingredient as IngredientModel;
use Kami\Cocktail\DataObjects\Ingredient\Ingredient as IngredientDTO;

class Matcher
{
    /** @var array<string, int> */
    private array $matchedIngredients = [];

    /** @var array<string, int> */
    private array $matchedGlasses = [];

    /** @var array<string, int> */
    private array $matchedMethods = [];

    public function __construct(private readonly int $userId, private readonly int $barId, private readonly IngredientService $ingredientService)
    {
    }

    public function matchOrCreateIngredientByName(Ingredient $ingredient): int
    {
        $matchName = mb_strtolower($ingredient->name, 'UTF-8');

        if (isset($this->matchedIngredients[$matchName])) {
            return $this->matchedIngredients[$matchName];
        }

        $existingIngredient = IngredientModel::whereRaw('LOWER(name) = ?', [$matchName])->where('bar_id', $this->barId)->first();
        if ($existingIngredient) {
            $this->matchedIngredients[$matchName] = $existingIngredient->id;

            return $existingIngredient->id;
        }

        $newIngredient = $this->ingredientService->createIngredient(new IngredientDTO(
            $this->barId,
            $ingredient->name,
            $this->userId,
            null,
            $ingredient->strength,
            $ingredient->description,
            $ingredient->origin
        ));

        $this->matchedIngredients[$matchName] = $newIngredient->id;

        return $newIngredient->id;
    }

    public function matchGlassByName(string $name): ?int
    {
        $matchName = mb_strtolower($name, 'UTF-8');

        if (isset($this->matchedGlasses[$matchName])) {
            return $this->matchedGlasses[$matchName];
        }

        $existingGlass = Glass::whereRaw('LOWER(name) = ?', [$matchName])->where('bar_id', $this->barId)->first();
        if ($existingGlass) {
            $this->matchedGlasses[$matchName] = $existingGlass->id;

            return $existingGlass->id;
        }

        return null;
    }

    public function matchMethodByName(string $name): ?int
    {
        $matchName = mb_strtolower($name, 'UTF-8');

        if (isset($this->matchedMethods[$matchName])) {
            return $this->matchedMethods[$matchName];
        }

        $existingMethod = CocktailMethod::whereRaw('LOWER(name) = ?', [$matchName])->where('bar_id', $this->barId)->first();
        if ($existingMethod) {
            $this->matchedMethods[$matchName] = $existingMethod->id;

            return $existingMethod->id;
        }

        return null;
    }
}
