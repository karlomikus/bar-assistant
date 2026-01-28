<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuCategory extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * @return HasMany<MenuCocktail, $this>
     */
    public function menuCocktails(): HasMany
    {
        return $this->hasMany(MenuCocktail::class)->orderBy('sort');
    }

    /**
     * @return HasMany<MenuIngredient, $this>
     */
    public function menuIngredients(): HasMany
    {
        return $this->hasMany(MenuIngredient::class)->orderBy('sort');
    }
}
