<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Kami\RecipeUtils\UnitConverter\Units;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CocktailIngredient extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $casts = [
        'optional' => 'boolean',
        'amount' => 'float',
        'amount_max' => 'float',
    ];

    /**
     * @return BelongsTo<Ingredient, CocktailIngredient>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * @return BelongsTo<Cocktail, CocktailIngredient>
     */
    public function cocktail(): BelongsTo
    {
        return $this->belongsTo(Cocktail::class);
    }

    /**
     * @return HasMany<CocktailIngredientSubstitute>
     */
    public function substitutes(): HasMany
    {
        return $this->hasMany(CocktailIngredientSubstitute::class);
    }

    public function getConvertedTo(Units $units): CocktailIngredientConverted
    {
        return new CocktailIngredientConverted($this, $units);
    }

    public function userHasInShelf(User $user): bool
    {
        $currentShelf = $user->getShelfIngredients($this->ingredient->bar_id);

        return $currentShelf->contains('ingredient_id', $this->ingredient_id);
    }

    public function userHasInShelfAsSubstitute(User $user): bool
    {
        $currentShelf = $user->getShelfIngredients($this->ingredient->bar_id);

        foreach ($this->substitutes as $sub) {
            if ($currentShelf->contains('ingredient_id', $sub->ingredient_id)) {
                return true;
            }
        }

        return false;
    }
}
