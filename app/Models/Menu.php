<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
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
     * @param array<int, array<string, mixed>> $cocktailMenuItems
     */
    public function syncCocktails(array $cocktailMenuItems): void
    {
        $validCocktails = DB::table('cocktails')
            ->select('id')
            ->where('bar_id', $this->bar_id)
            ->whereIn('id', array_column($cocktailMenuItems, 'cocktail_id'))
            ->pluck('id')
            ->toArray();

        $currentMenuItems = [];
        foreach ($cocktailMenuItems as $cocktailMenuItem) {
            if (!in_array($cocktailMenuItem['cocktail_id'], $validCocktails)) {
                continue;
            }

            $price = 0;
            if ($cocktailMenuItem['price'] ?? false) {
                $price = Money::of(
                    $cocktailMenuItem['price'],
                    $cocktailMenuItem['currency'],
                    roundingMode: RoundingMode::UP
                )->getMinorAmount()->toInt();
            }

            $currentMenuItems[] = $cocktailMenuItem['cocktail_id'];
            $this->menuCocktails()->updateOrCreate([
                'cocktail_id' => $cocktailMenuItem['cocktail_id']
            ], [
                'category_name' => $cocktailMenuItem['category_name'],
                'sort' => $cocktailMenuItem['sort'] ?? 0,
                'price' => $price,
                'currency' => $cocktailMenuItem['currency'] ?? null,
            ]);
        }

        $this->menuCocktails()->whereNotIn('cocktail_id', $currentMenuItems)->delete();
    }

    /**
     * @param array<int, array<string, mixed>> $cocktailMenuItems
     */
    public function syncIngredients(array $cocktailMenuItems): void
    {
        $validIngredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $this->bar_id)
            ->whereIn('id', array_column($cocktailMenuItems, 'cocktail_id'))
            ->pluck('id')
            ->toArray();

        $currentMenuItems = [];
        foreach ($cocktailMenuItems as $cocktailMenuItem) {
            if (!in_array($cocktailMenuItem['ingredient_id'], $validIngredients)) {
                continue;
            }

            $price = 0;
            if ($cocktailMenuItem['price'] ?? false) {
                $price = Money::of(
                    $cocktailMenuItem['price'],
                    $cocktailMenuItem['currency'],
                    roundingMode: RoundingMode::UP
                )->getMinorAmount()->toInt();
            }

            $currentMenuItems[] = $cocktailMenuItem['ingredient_id'];
            $this->menuIngredients()->updateOrCreate([
                'ingredient_id' => $cocktailMenuItem['ingredient_id']
            ], [
                'category_name' => $cocktailMenuItem['category_name'],
                'sort' => $cocktailMenuItem['sort'] ?? 0,
                'price' => $price,
                'currency' => $cocktailMenuItem['currency'] ?? null,
            ]);
        }

        $this->menuIngredients()->whereNotIn('ingredient_id', $currentMenuItems)->delete();
    }
}
