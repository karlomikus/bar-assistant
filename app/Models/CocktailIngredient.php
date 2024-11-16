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
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\CocktailIngredientFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $casts = [
        'optional' => 'boolean',
        'amount' => 'float',
        'amount_max' => 'float',
    ];

    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * @return BelongsTo<Cocktail, $this>
     */
    public function cocktail(): BelongsTo
    {
        return $this->belongsTo(Cocktail::class);
    }

    /**
     * @return HasMany<CocktailIngredientSubstitute, $this>
     */
    public function substitutes(): HasMany
    {
        return $this->hasMany(CocktailIngredientSubstitute::class);
    }

    /**
     * @param array<Units> $ignoreUnits
     */
    public function getConvertedTo(?Units $units = null, array $ignoreUnits = [Units::Dash]): CocktailIngredientFormatter
    {
        return new CocktailIngredientFormatter($this, $units, $ignoreUnits);
    }

    public function userHasInShelf(User $user): bool
    {
        return $this->ingredient->userHasInShelf($user);
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

    public function userHasInShelfAsComplexIngredient(User $user): bool
    {
        $requiredIngredientIds = $this->ingredient->ingredientParts->pluck('ingredient_id');
        if ($requiredIngredientIds->isEmpty()) {
            return false;
        }

        $currentShelf = $user->getShelfIngredients($this->ingredient->bar_id)->pluck('ingredient_id');

        return $requiredIngredientIds->every(fn ($id) => $currentShelf->contains($id));
    }

    public function barHasInShelf(): bool
    {
        return $this->ingredient->barHasInShelf();
    }

    public function barHasInShelfAsSubstitute(): bool
    {
        $currentShelf = $this->ingredient->bar->shelfIngredients;

        foreach ($this->substitutes as $sub) {
            if ($currentShelf->contains('ingredient_id', $sub->ingredient_id)) {
                return true;
            }
        }

        return false;
    }

    public function barHasInShelfAsComplexIngredient(): bool
    {
        $requiredIngredientIds = $this->ingredient->ingredientParts->pluck('ingredient_id');
        if ($requiredIngredientIds->isEmpty()) {
            return false;
        }

        $currentShelf = $this->ingredient->bar->shelfIngredients->pluck('ingredient_id');

        return $requiredIngredientIds->every(fn ($id) => $currentShelf->contains($id));
    }

    public function getMinPriceInCategory(PriceCategory $priceCategory): ?IngredientPrice
    {
        return $this
            ->ingredient
            ->prices
            ->sortBy('price')
            ->where('price_category_id', $priceCategory->id)
            ->where('units', $this->units)
            ->first();
    }
}
