<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class ShoppingList
{
    #[OAT\Property()]
    public IngredientBasic $ingredient;
    #[OAT\Property(example: 3)]
    public int $quantity;
}
