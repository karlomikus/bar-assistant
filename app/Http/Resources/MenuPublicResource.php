<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Kami\Cocktail\Models\MenuCocktail;
use Kami\Cocktail\Models\ValueObjects\Price;
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
                            'price' => new PriceResource(new Price($menuCocktail->getMoney())),
                            'public_id' => $menuCocktail->cocktail->public_id,
                            'name' => $menuCocktail->cocktail->name,
                            'short_ingredients' => $menuCocktail->cocktail->getIngredientNames(),
                            'image' => config('app.url') . $menuCocktail->cocktail->getMainImageThumbUrl(false),
                        ];
                    }),
                ];
            })->values()
        ];
    }
}
