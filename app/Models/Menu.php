<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Menu extends Model
{
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
}
