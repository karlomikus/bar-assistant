<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Kami\Cocktail\Models\MenuCocktail;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Menu
 */
class MenuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'is_enabled' => $this->is_enabled,
            'created_at' => $this->created_at->toJson(),
            'updated_at' => $this->updated_at?->toJson(),
            'categories' => $this->menuCocktails->groupBy('category_name')->map(function ($categoryCocktails, $name) {
                return [
                    'name' => $name,
                    'cocktails' => $categoryCocktails->map(function (MenuCocktail $menuCocktail) {
                        return [
                            'id' => $menuCocktail->cocktail_id,
                            'slug' => $menuCocktail->cocktail->slug,
                            'sort' => $menuCocktail->sort,
                            'price' => number_format($menuCocktail->price / 100, 2),
                            'currency' => $menuCocktail->currency,
                            'name' => $menuCocktail->cocktail->name,
                            'short_ingredients' => $menuCocktail->cocktail->getShortIngredients(),
                        ];
                    }),
                ];
            })->values()
        ];
    }
}
