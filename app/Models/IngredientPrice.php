<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IngredientPrice extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<Ingredient, IngredientPrice>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * @return BelongsTo<PriceCategory, IngredientPrice>
     */
    public function priceCategory(): BelongsTo
    {
        return $this->belongsTo(PriceCategory::class);
    }

    public function getMoney(): Money
    {
        return Money::ofMinor($this->price, $this->priceCategory->currency);
    }
}
