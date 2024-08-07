<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['id', 'name', 'description', 'ingredients_count'])]
class IngredientCategory
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'Spirits')]
    public string $name;
    #[OAT\Property(example: 'Category of base spirits')]
    public ?string $description = null;
    #[OAT\Property(property: 'ingredients_count', example: 32)]
    public int $ingredientsCount;
}