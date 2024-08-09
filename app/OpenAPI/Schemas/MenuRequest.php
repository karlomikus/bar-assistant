<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['is_enabled', 'cocktails'])]
class MenuRequest
{
    #[OAT\Property(property: 'is_enabled')]
    public bool $isEnabled = false;
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'integer', property: 'cocktail_id', example: 1),
        new OAT\Property(type: 'string', property: 'price', example: '22.52'),
        new OAT\Property(type: 'string', property: 'category_name', example: 'Category name'),
        new OAT\Property(type: 'integer', property: 'sort', example: 1),
        new OAT\Property(type: 'string', property: 'currency', example: 'EUR'),
    ]))]
    public array $cocktails = [];
}
