<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Kami\Cocktail\Models\MenuCocktail;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Menu
 */
class MenuPublicResource extends JsonResource
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
            'bar' => [
                'name' => $this->bar->name,
                'subtitle' => $this->bar->subtitle,
                'description' => $this->bar->description,
            ],
            'categories' => $this->menuCocktails->groupBy('category_name')->map(function ($categoryCocktails, $name) {
                return [
                    'name' => $name,
                    'cocktails' => $categoryCocktails->map(function (MenuCocktail $menuCocktail) {
                        return [
                            'sort' => $menuCocktail->sort,
                            'price' => [
                                'full' => $menuCocktail->price,
                                'formatted' => number_format($menuCocktail->price / 100, 2),
                            ],
                            'public_id' => $menuCocktail->cocktail->public_id,
                            'slug' => $menuCocktail->cocktail->slug,
                            'currency' => $menuCocktail->currency,
                            'name' => $menuCocktail->cocktail->name,
                            'short_ingredients' => $menuCocktail->cocktail->getIngredientNames(),
                            'image' => $menuCocktail->cocktail->getMainImageUrl(),
                        ];
                    }),
                ];
            })->values()
        ];
    }
}
