<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Kami\Cocktail\Models\ValueObjects\MenuItem;
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
            'categories' => $this->getMenuItems()->groupBy('categoryName')->map(function ($categoryCocktails, $name) {
                return [
                    'name' => $name,
                    'items' => $categoryCocktails->sortBy(fn ($menuItem) => $menuItem->sort)->values()->map(function (MenuItem $menuItem) {
                        return [
                            'in_bar_shelf' => false,
                            'type' => $menuItem->type->value,
                            'sort' => $menuItem->sort,
                            'price' => new PriceResource($menuItem->price),
                            'public_id' => $menuItem->publicId,
                            'name' => $menuItem->name,
                            'description' => $menuItem->description,
                            'image' => $menuItem->image,
                        ];
                    }),
                ];
            })->values()
        ];
    }
}
