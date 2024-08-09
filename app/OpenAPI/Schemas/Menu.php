<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
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
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'string', property: 'name', example: 'Category name'),
        new OAT\Property(type: 'array', property: 'cocktails', items: new OAT\Items(type: 'object', properties: [
            new OAT\Property(type: 'integer', property: 'id', example: 1),
            new OAT\Property(type: 'string', property: 'slug', example: 'cocktail-name-1'),
            new OAT\Property(type: 'integer', property: 'sort', example: 1),
            new OAT\Property(type: 'string', property: 'price', example: 'EUR 23.85'),
            new OAT\Property(type: 'string', property: 'currency', example: 'EUR'),
            new OAT\Property(type: 'string', property: 'name', example: 'Cocktail name'),
            new OAT\Property(type: 'array', property: 'short_ingredients', items: new OAT\Items(type: 'string', example: 'Vodka')),
        ])),
    ]))]
    public array $categories;
}
