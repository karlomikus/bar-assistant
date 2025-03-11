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
                'images' => $this->when(
                    $this->bar->relationLoaded('images'),
                    fn () => ImageResource::collection($this->bar->images)
                ),
            ],
            'categories' => $this->getMenuItems()->groupBy('categoryName')->map(function ($items, $name) {
                return [
                    'name' => $name,
                    'items' => $items->sortBy(fn ($menuItem) => $menuItem->sort)->values()->map(function (MenuItem $menuItem) {
                        return [
                            'in_bar_shelf' => $menuItem->inShelf,
                            'type' => $menuItem->type->value,
                            'sort' => $menuItem->sort,
                            'price' => new PriceResource($menuItem->price),
                            'public_id' => $menuItem->publicId,
                            'name' => $menuItem->name,
                            'description' => $menuItem->description,
                            'image' => $menuItem->image,
                        ];
                    })->toArray(),
                ];
            })->values()
        ];
    }
}
