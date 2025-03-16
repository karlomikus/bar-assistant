<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Enums\MenuItemTypeEnum;

#[OAT\Schema(required: ['id', 'is_enabled', 'created_at', 'updated_at', 'categories'])]
class Menu
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(property: 'is_enabled')]
    public bool $isEnabled = false;
    #[OAT\Property(property: 'created_at', format: 'date-time')]
    public string $createdAt;
    #[OAT\Property(property: 'updated_at', format: 'date-time')]
    public ?string $updatedAt = null;
    /** @var array<mixed> */
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'string', property: 'name', example: 'Category name'),
        new OAT\Property(type: 'array', property: 'items', items: new OAT\Items(type: 'object', properties: [
            new OAT\Property(type: 'integer', property: 'id', example: 1),
            new OAT\Property(property: 'type', ref: MenuItemTypeEnum::class),
            new OAT\Property(type: 'integer', property: 'sort', example: 1),
            new OAT\Property(property: 'price', ref: Price::class),
            new OAT\Property(type: 'string', property: 'name', example: 'Cocktail name'),
            new OAT\Property(type: 'string', property: 'description'),
        ], required: ['id', 'type', 'sort', 'price', 'name', 'description'])),
    ], required: ['name', 'items']))]
    public array $categories;
}
