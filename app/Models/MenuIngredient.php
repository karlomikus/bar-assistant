<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Brick\Money\Money;
use Brick\Money\Currency;
use Illuminate\Database\Eloquent\Model;
use Brick\Money\Exception\UnknownCurrencyException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MenuIngredient extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\MenuIngredientFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'ingredient_id',
        'category_name',
        'sort',
        'price',
        'currency',
    ];

    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
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
