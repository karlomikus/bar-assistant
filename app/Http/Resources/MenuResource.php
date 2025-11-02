<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\ValueObjects\MenuItem;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Models\Enums\MenuItemTypeEnum;

/**
 * @mixin \Kami\Cocktail\Models\Menu
 */
#[OAT\Schema(
    schema: 'Menu',
    description: 'Menu resource',
    properties: [
        new OAT\Property(property: 'id', example: 1, type: 'integer', description: 'Menu ID'),
        new OAT\Property(property: 'is_enabled', type: 'boolean', example: true, description: 'Is menu enabled'),
        new OAT\Property(property: 'created_at', format: 'date-time', type: 'string', description: 'Creation date'),
        new OAT\Property(property: 'updated_at', format: 'date-time', type: 'string', description: 'Last update date', nullable: true),
        new OAT\Property(property: 'categories', type: 'array', items: new OAT\Items(type: 'object', properties: [
            new OAT\Property(type: 'string', property: 'name', example: 'Category name'),
            new OAT\Property(type: 'array', property: 'items', items: new OAT\Items(type: 'object', properties: [
                new OAT\Property(type: 'integer', property: 'id', example: 1),
                new OAT\Property(property: 'type', ref: MenuItemTypeEnum::class),
                new OAT\Property(type: 'integer', property: 'sort', example: 1),
                new OAT\Property(property: 'price', ref: PriceResource::class),
                new OAT\Property(type: 'string', property: 'name', example: 'Cocktail name', description: 'Cocktail name'),
                new OAT\Property(type: 'string', property: 'description', nullable: true, example: 'Cocktail description'),
            ], required: ['id', 'type', 'sort', 'price', 'name', 'description'])),
        ], required: ['name', 'items']))
    ],
    required: ['id', 'is_enabled', 'created_at', 'updated_at', 'categories']
)]
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
            'categories' => $this->getMenuItems()->groupBy('categoryName')->map(fn ($items, $name) => [
                'name' => $name,
                'items' => $items->sortBy(fn ($menuItem) => $menuItem->sort)->values()->map(fn (MenuItem $menuItem) => [
                    'id' => $menuItem->id,
                    'type' => $menuItem->type->value,
                    'sort' => $menuItem->sort,
                    'price' => new PriceResource($menuItem->price),
                    'name' => $menuItem->name,
                    'description' => $menuItem->description,
                ])->toArray(),
            ])->values()
        ];
    }
}
