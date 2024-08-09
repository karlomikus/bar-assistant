<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class ShoppingList
{
    #[OAT\Property(property: 'ingredient_id', example: 1)]
    public int $ingredientId;
    #[OAT\Property(property: 'ingredient_name', example: 'gin-1')]
    public string $ingredientName;
    #[OAT\Property(property: 'ingredient_slug', example: 'gin-1')]
    public string $ingredientSlug;
}
