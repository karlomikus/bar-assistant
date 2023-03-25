<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CocktailIngredientSubstitute extends Model
{
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
}
