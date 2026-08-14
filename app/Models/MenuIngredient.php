<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Brick\Money\Money;
use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MenuIngredient extends BaseModel
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\MenuIngredientFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'ingredient_id',
        'sort',
        'price',
        'currency',
        'is_bar_inventory_aware',
        'menu_category_id',
    ];

    protected function casts(): array
    {
        return [
            'is_bar_inventory_aware' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * @return BelongsTo<MenuCategory, $this>
     */
    public function menuCategory(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class);
    }

    public function getMoney(): Money
    {
        if ($this->currency === null) {
            $currency = 'EUR';
        } else {
            try {
                $currency = Currency::of($this->currency);
            } catch (UnknownCurrencyException) {
                // Prior to inclusion of Money object, currency could be any string
                // To handle migration cases, we'll fallback to EUR
                $currency = 'EUR';
            }
        }

        return Money::ofMinor($this->price, $currency);
    }
}
