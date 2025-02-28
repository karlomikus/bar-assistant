<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Kami\Cocktail\Models\ValueObjects\MenuItem;
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
            'is_enabled' => (bool) $this->is_enabled,
            'created_at' => $this->created_at->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
            'categories' => $this->getMenuItems()->groupBy('categoryName')->map(function ($items, $name) {
                return [
                    'name' => $name,
                    'items' => $items->sortBy(fn ($menuItem) => $menuItem->sort)->values()->map(function (MenuItem $menuItem) {
                        return [
                            'id' => $menuItem->id,
                            'type' => $menuItem->type->value,
                            'sort' => $menuItem->sort,
                            'price' => new PriceResource($menuItem->price),
                            'name' => $menuItem->name,
                            'description' => $menuItem->description,
                        ];
                    }),
                ];
            })->values()
        ];
    }
}
