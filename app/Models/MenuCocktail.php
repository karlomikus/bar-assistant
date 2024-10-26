<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuCocktail extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'cocktail_id',
        'category_name',
        'sort',
        'price',
        'currency',
    ];

    /**
     * @return BelongsTo<Cocktail, $this>
     */
    public function cocktail(): BelongsTo
    {
        return $this->belongsTo(Cocktail::class);
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
        return Money::ofMinor($this->price, $this->currency ?? 'EUR');
    }
}
