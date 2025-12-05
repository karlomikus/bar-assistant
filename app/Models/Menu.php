<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Models\ValueObjects\MenuItem;
use Kami\Cocktail\Models\Enums\MenuItemTypeEnum;
use Kami\Cocktail\OpenAPI\Schemas\MenuItemRequest;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\MenuFactory> */
    use HasFactory;

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    protected $fillable = ['bar_id'];

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

    /**
     * @return BelongsTo<Bar, $this>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }

    /**
     * @return Collection<array-key, MenuItem>
     */
    public function getMenuItems(): Collection
    {
        $cocktails = $this->menuCocktails->map(fn (MenuCocktail $menuCocktail) => MenuItem::fromMenuCocktail($menuCocktail))->values();
        $ingredients = $this->menuIngredients->map(fn (MenuIngredient $menuIngredient) => MenuItem::fromMenuIngredient($menuIngredient))->values();

        return collect([...$cocktails, ...$ingredients]);
    }

    /**
     * @param array<MenuItemRequest> $menuItems
     */
    public function syncItems(array $menuItems): void
    {
        $currentIngredientMenuItems = [];
        $currentCocktailMenuItems = [];

        foreach ($menuItems as $menuItem) {
            $price = 0;
            if ($menuItem->price ?? false) {
                $price = Money::of(
                    $menuItem->price,
                    $menuItem->currency,
                    roundingMode: RoundingMode::UP
                )->getMinorAmount()->toInt();
            }

            if ($menuItem->type === MenuItemTypeEnum::Ingredient) {
                $currentIngredientMenuItems[] = $menuItem->id;
                $this->menuIngredients()->updateOrCreate([
                    'ingredient_id' => $menuItem->id
                ], [
                    'category_name' => $menuItem->categoryName,
                    'sort' => $menuItem->sort ?? 0,
                    'price' => $price,
                    'currency' => $menuItem->currency ?? null,
                ]);
            } else {
                $currentCocktailMenuItems[] = $menuItem->id;
                $this->menuCocktails()->updateOrCreate([
                    'cocktail_id' => $menuItem->id
                ], [
                    'category_name' => $menuItem->categoryName,
                    'sort' => $menuItem->sort ?? 0,
                    'price' => $price,
                    'currency' => $menuItem->currency ?? null,
                ]);
            }
        }

        $this->menuIngredients()->whereNotIn('ingredient_id', $currentIngredientMenuItems)->delete();
        $this->menuCocktails()->whereNotIn('cocktail_id', $currentCocktailMenuItems)->delete();
    }
}
