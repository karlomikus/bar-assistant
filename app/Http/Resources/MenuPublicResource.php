<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\ValueObjects\MenuItem;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Models\Enums\MenuItemTypeEnum;

/**
 * @mixin \Kami\Cocktail\Models\Menu
 */
#[OAT\Schema(
    schema: 'MenuPublic',
    description: 'Menu resource',
    properties: [
        new OAT\Property(property: 'bar', type: 'object', description: 'Bar information', properties: [
            new OAT\Property(property: 'name', type: 'string', example: 'Bar name'),
            new OAT\Property(property: 'subtitle', type: 'string', example: 'Bar subtitle', nullable: true),
            new OAT\Property(property: 'description', type: 'string', example: 'Bar description', nullable: true),
            new OAT\Property(property: 'images', type: 'array', items: new OAT\Items(type: 'string'), description: 'Bar images (like bar logo)', example: [
                'https://example.com/image1.jpg',
                'https://example.com/image2.jpg',
            ]),
        ], required: ['name', 'subtitle', 'description']),
        new OAT\Property(
            property: 'categories',
            type: 'array',
            description: 'List of menu categories',
            items: new OAT\Items(type: 'object', properties: [
                new OAT\Property(property: 'name', type: 'string', example: 'Category name'),
                new OAT\Property(
                    property: 'items',
                    type: 'array',
                    items: new OAT\Items(type: 'object', properties: [
                        new OAT\Property(property: 'in_bar_shelf', type: 'boolean', example: false),
                        new OAT\Property(property: 'type', type: MenuItemTypeEnum::class),
                        new OAT\Property(property: 'sort', type: 'integer', example: 1),
                        new OAT\Property(property: 'price', type: PriceResource::class),
                        new OAT\Property(property: 'public_id', type: 'string', example: '01ARZ3NDEKTSV4RRFFQ69G5FAV', nullable: true),
                        new OAT\Property(property: 'name', type: 'string', example: 'Cocktail name'),
                        new OAT\Property(property: 'description', type: 'string', nullable: true),
                        new OAT\Property(property: 'image', type: 'string', nullable: true, description: 'Image URL'),
                    ], required: ['in_bar_shelf', 'type', 'sort', 'price', 'public_id', 'name', 'description'])
                ),
                ], required: ['name', 'items']),
        ),
    ],
    required: ['bar', 'categories']
)]
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
                    fn () => $this->bar->images->map(fn (Image $image) => $image->getImageUrl())->toArray(),
                ),
            ],
            'categories' => $this->getMenuItems()->groupBy('categoryName')->map(fn ($items, $name) => [
                'name' => $name,
                'items' => $items->sortBy(fn ($menuItem) => $menuItem->sort)->values()->map(fn (MenuItem $menuItem) => [
                    'in_bar_shelf' => $menuItem->inShelf,
                    'type' => $menuItem->type->value,
                    'sort' => $menuItem->sort,
                    'price' => new PriceResource($menuItem->price),
                    'public_id' => $menuItem->publicId,
                    'name' => $menuItem->name,
                    'description' => $menuItem->description,
                    'image' => $menuItem->image,
                ])->toArray(),
            ])->values()
        ];
    }
}
