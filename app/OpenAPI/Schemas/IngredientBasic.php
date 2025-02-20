<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(description: 'Minimal ingredient information', required: ['id', 'slug', 'name', 'materialized_path'])]
class IngredientBasic
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'gin-1')]
    public string $slug;
    #[OAT\Property(example: 'Gin')]
    public string $name;
}
