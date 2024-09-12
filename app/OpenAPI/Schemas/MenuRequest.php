<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['is_enabled', 'cocktails'])]
class MenuRequest
{
    #[OAT\Property(property: 'is_enabled')]
    public bool $isEnabled = false;
    /** @var array<mixed> */
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'integer', property: 'cocktail_id', example: 1),
        new OAT\Property(type: 'integer', property: 'price', example: 2252, format: 'minor'),
        new OAT\Property(type: 'string', property: 'category_name', example: 'Category name'),
        new OAT\Property(type: 'integer', property: 'sort', example: 1),
        new OAT\Property(type: 'string', property: 'currency', example: 'EUR', format: 'ISO 4217'),
    ]))]
    public array $cocktails = [];
}
