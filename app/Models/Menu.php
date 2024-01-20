<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends Model
{
    use HasFactory;

    protected $casts = [
        'is_enabled' => 'boolean',
    ];


    /**
     * @return HasMany<MenuCocktail>
     */
    public function menuCocktails(): HasMany
    {
        return $this->hasMany(MenuCocktail::class)->orderBy('sort');
    }

    /**
     * @return BelongsTo<Bar, Menu>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }

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

            $price = str_replace([','], '.', $cocktailMenuItem['price'] ?? '0');
            $price = (float) number_format((float) $price, 2);
            $price *= 100;

            $currentMenuItems[] = $cocktailMenuItem['cocktail_id'];
            $this->menuCocktails()->updateOrCreate([
                'cocktail_id' => $cocktailMenuItem['cocktail_id']
            ], [
                'category_name' => $cocktailMenuItem['category_name'],
                'sort' => $cocktailMenuItem['sort'],
                'price' => $price,
                'currency' => $cocktailMenuItem['currency'] ?? null,
            ]);
        }

        $this->menuCocktails()->whereNotIn('cocktail_id', $currentMenuItems)->delete();
    }
}
