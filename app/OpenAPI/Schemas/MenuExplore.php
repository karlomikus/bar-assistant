<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Enums\MenuItemTypeEnum;

#[OAT\Schema()]
class MenuExplore
{
    /** @var array<mixed> */
    #[OAT\Property(type: 'object', properties: [
        new OAT\Property(type: 'string', property: 'name', example: 'Bar name'),
        new OAT\Property(type: 'string', property: 'subtitle', example: 'Bar subtitle'),
        new OAT\Property(type: 'array', property: 'images', items: new OAT\Items(type: 'string', example: 'https://example.com/image.jpg')),
    ])]
    public array $bar;
    /** @var array<mixed> */
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'string', property: 'name', example: 'Category name'),
        new OAT\Property(type: 'array', property: 'items', items: new OAT\Items(type: 'object', properties: [
            new OAT\Property(type: 'bool', property: 'in_bar_shelf', example: false),
            new OAT\Property(property: 'type', ref: MenuItemTypeEnum::class),
            new OAT\Property(type: 'integer', property: 'sort', example: 1),
            new OAT\Property(property: 'price', ref: Price::class),
            new OAT\Property(type: 'string', property: 'public_id', example: '01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new OAT\Property(type: 'string', property: 'name', example: 'Cocktail name'),
            new OAT\Property(type: 'string', property: 'description'),
            new OAT\Property(type: 'string', nullable: true, property: 'image', description: 'Image URL'),
        ])),
    ]))]
    public array $categories;
}
