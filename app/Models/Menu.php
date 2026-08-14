<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends BaseModel
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\MenuFactory> */
    use HasFactory;

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    protected $fillable = ['bar_id'];

    protected $with = [
        'categories.menuCocktails.cocktail.ingredients.ingredient',
        'categories.menuCocktails.cocktail.images',
        'categories.menuCocktails.cocktail.bar.shelfIngredients',
        'categories.menuIngredients.ingredient.ancestors',
        'categories.menuIngredients.ingredient.images',
        'categories.menuIngredients.ingredient.bar.shelfIngredients'
    ];

    /**
     * @return HasMany<MenuCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(MenuCategory::class)->orderBy('sort');
    }

    /**
     * @return BelongsTo<Bar, $this>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }
}
