<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['ingredients'])]
class ShoppingListRequest
{
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(property: 'id', type: 'integer'),
        new OAT\Property(property: 'quantity', type: 'integer'),
    ]))]
    public array $ingredients;
}
