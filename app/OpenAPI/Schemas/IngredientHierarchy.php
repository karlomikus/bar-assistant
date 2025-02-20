<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(description: 'Ingredient hierarchy')]
class IngredientHierarchy
{
    #[OAT\Property(example: 'Spirits > Gin', property: 'path_to_self')]
    public int $pathToSelf;

    #[OAT\Property(property: 'parent_ingredient')]
    public ?IngredientBasic $parentIngredient = null;

    /** @var IngredientBasic[] */
    #[OAT\Property()]
    public array $descendants = [];

    /** @var IngredientBasic[] */
    #[OAT\Property()]
    public array $ancestors = [];
}
