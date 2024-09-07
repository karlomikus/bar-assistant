<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Kami\RecipeUtils\UnitConverter\Units;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CocktailIngredientSubstitute extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\CocktailIngredientSubstituteFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<CocktailIngredient, CocktailIngredientSubstitute>
     */
    public function cocktailIngredient(): BelongsTo
    {
        return $this->belongsTo(CocktailIngredient::class);
    }

    /**
     * @return BelongsTo<Ingredient, CocktailIngredientSubstitute>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function getConvertedTo(?Units $units = null): CocktailIngredientFormatter
    {
        return new CocktailIngredientFormatter($this, $units);
    }

    public function userHasInShelf(User $user): bool
    {
        $currentShelf = $user->getShelfIngredients($this->ingredient->bar_id);

        return $currentShelf->contains('ingredient_id', $this->ingredient_id);
    }
}
