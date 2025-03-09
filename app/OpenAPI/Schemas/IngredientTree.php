<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['ingredient', 'children'])]
class IngredientTree
{
    #[OAT\Property()]
    public IngredientBasic $ingredient;

    /** @var IngredientTree[] */
    #[OAT\Property()]
    public array $children;
}
