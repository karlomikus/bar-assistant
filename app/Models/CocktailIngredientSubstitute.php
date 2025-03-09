<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kami\Cocktail\Models\ValueObjects\UnitValueObject;
use Kami\Cocktail\Models\ValueObjects\AmountValueObject;

class CocktailIngredientSubstitute extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\CocktailIngredientSubstituteFactory> */
    use HasFactory;

    protected $casts = [
        'amount' => 'float',
        'amount_max' => 'float',
    ];

    /**
     * @return BelongsTo<CocktailIngredient, $this>
     */
    public function cocktailIngredient(): BelongsTo
    {
        return $this->belongsTo(CocktailIngredient::class);
    }

    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function getAmount(): AmountValueObject
    {
        return new AmountValueObject(
            $this->amount ?? 0.0,
            new UnitValueObject($this->units ?? ''),
            $this->amount_max,
        );
    }

    public function userHasInShelf(User $user): bool
    {
        $currentShelf = $user->getShelfIngredients($this->ingredient->bar_id);

        return $currentShelf->contains('ingredient_id', $this->ingredient_id);
    }

    public function barHasInShelf(): bool
    {
        return $this->ingredient->bar->shelfIngredients->contains('ingredient_id', $this->ingredient_id);
    }
}
