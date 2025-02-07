<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Brick\Money\RationalMoney;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kami\Cocktail\Models\ValueObjects\UnitValueObject;
use Kami\Cocktail\Models\ValueObjects\AmountValueObject;

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

        return $this->barHasVariantInShelf();
    }

    public function barHasVariantInShelf(): bool
    {
        $currentShelf = $this->ingredient->bar->shelfIngredients;

        return $this->ingredient->pathDescendants()->whereIn('id', $currentShelf->pluck('ingredient_id'))->count() > 0;
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

    public function getAmount(): AmountValueObject
    {
        return new AmountValueObject(
            $this->amount,
            new UnitValueObject($this->units),
            $this->amount_max,
        );
    }

    /**
     * Return the price per use in the given price category.
     * Converts ingredient amount to match the price amount if possible.
     *
     * @param PriceCategory $priceCategory
     */
    public function getConvertedPricePerUse(PriceCategory $priceCategory): ?RationalMoney
    {
        // Price already converted to cocktail ingredient units
        $ingredientPrice = $this->getMinConvertedPriceInCategory($priceCategory);

        if ($ingredientPrice === null) {
            return null;
        }

        // Convert current ingredient amount to price units
        $convertedLocalAmount = $this->getAmount()->convertTo(new UnitValueObject($ingredientPrice->units));

        try {
            $pricePerUse = $ingredientPrice->getPricePerUnit()->multipliedBy($convertedLocalAmount->amountMin);
        } catch (\Throwable) {
            return null;
        }

        if ($pricePerUse->isLessThanOrEqualTo(0)) {
            $pricePerUse = $pricePerUse->plus(0.01);
        }

        return $pricePerUse;
    }

    public function getMinConvertedPriceInCategory(PriceCategory $priceCategory): ?IngredientPrice
    {
        return $this
            ->ingredient
            ->getPricesWithConvertedUnits($this->units)
            ->sortBy('price')
            ->where('price_category_id', $priceCategory->id)
            ->where('units', $this->units)
            ->first();
    }

    /**
     * @return Collection<int, CocktailIngredientSubstitute>
     */
    public function getPossibleSubstitutes(): \Illuminate\Support\Collection
    {
        $specificSubstitutes = $this->substitutes;

        $currentShelf = $this->ingredient->bar->shelfIngredients;
        $variants = $this->ingredient->pathDescendants()->whereIn('id', $currentShelf->pluck('ingredient_id'))->get();

        foreach ($variants as $variant) {
            $extraSub = new CocktailIngredientSubstitute();
            $extraSub->ingredient_id = $variant->id;

            $specificSubstitutes->push($extraSub);
        }

        return $specificSubstitutes->unique('ingredient_id');
    }
}
