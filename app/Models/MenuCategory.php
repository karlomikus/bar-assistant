<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Support\Collection;
use Kami\Cocktail\Models\ValueObjects\MenuItem;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MenuCategory extends BaseModel
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\MenuCategoryFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'menu_id',
        'sort',
        'name',
        'is_enabled',
    ];

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * @return Collection<int, MenuItem>
     */
    public function getMenuItems(): Collection
    {
        $results = [];
        foreach ($this->menuCocktails as $menuCocktail) {
            $results[] = MenuItem::fromMenuCocktail($menuCocktail);
        }
        foreach ($this->menuIngredients as $menuIngredient) {
            $results[] = MenuItem::fromMenuIngredient($menuIngredient);
        }

        return collect($results)->sortBy('sort');
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
