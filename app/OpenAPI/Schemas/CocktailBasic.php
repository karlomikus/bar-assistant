<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(description: 'Minimal cocktail information')]
class CocktailBasic
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'old-fashioned-1')]
    public string $slug;
    #[OAT\Property(example: 'Old Fashioned')]
    public string $name;
}
