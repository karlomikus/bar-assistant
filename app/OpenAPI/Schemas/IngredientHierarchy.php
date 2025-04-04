<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Http\Resources\IngredientBasicResource;

#[OAT\Schema(description: 'Ingredient hierarchy')]
class IngredientHierarchy
{
    #[OAT\Property(example: 'Spirits > Gin', property: 'path_to_self', description: 'Path to the current ingredient from the root')]
    public string $pathToSelf;

    #[OAT\Property(property: 'parent_ingredient')]
    public ?IngredientBasicResource $parentIngredient = null;

    /** @var IngredientBasicResource[] */
    #[OAT\Property()]
    public array $descendants = [];

    /** @var IngredientBasicResource[] */
    #[OAT\Property()]
    public array $ancestors = [];

    #[OAT\Property(property: 'root_ingredient_id', description: 'Root ingredient ID')]
    public ?string $rootIngredientId = null;
}
