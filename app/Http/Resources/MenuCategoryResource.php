<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\ValueObjects\MenuItem;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Models\Enums\MenuItemTypeEnum;

/**
 * @mixin \Kami\Cocktail\Models\MenuCategory
 */
#[OAT\Schema(
    schema: 'MenuCategory',
    description: 'Menu Category resource',
    properties: [
        new OAT\Property(type: 'string', property: 'name', example: 'Category name'),
        new OAT\Property(type: 'integer', property: 'sort', example: 1),
        new OAT\Property(type: 'boolean', property: 'is_enabled', example: true),
        new OAT\Property(type: 'array', property: 'items', items: new OAT\Items(type: 'object', properties: [
            new OAT\Property(type: 'integer', property: 'id', example: 1),
            new OAT\Property(property: 'type', ref: MenuItemTypeEnum::class),
            new OAT\Property(type: 'integer', property: 'sort', example: 1),
            new OAT\Property(property: 'price', ref: PriceResource::class),
            new OAT\Property(type: 'string', property: 'name', example: 'Cocktail name', description: 'Cocktail name'),
            new OAT\Property(type: 'string', property: 'description', nullable: true, example: 'Cocktail description'),
            new OAT\Property(type: 'bool', property: 'is_bar_inventory_aware', example: false, description: 'Indicates if the item is in the bar inventory'),
        ], required: ['id', 'type', 'sort', 'price', 'name', 'description', 'is_bar_inventory_aware'])),
    ],
    required: ['name', 'sort', 'items']
)]
class MenuCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'sort' => $this->sort,
            'is_enabled' => (bool) $this->is_enabled,
            'items' => $this->getMenuItems()->map(static fn (MenuItem $menuItem): array => [
                'id' => $menuItem->id,
                'type' => $menuItem->type->value,
                'sort' => $menuItem->sort,
                'price' => new PriceResource($menuItem->price),
                'name' => $menuItem->name,
                'description' => $menuItem->description,
                'is_bar_inventory_aware' => $menuItem->isBarInventoryAware,
                'in_shelf' => $menuItem->inShelf,
            ])->values()->toArray(),
        ];
    }
}
