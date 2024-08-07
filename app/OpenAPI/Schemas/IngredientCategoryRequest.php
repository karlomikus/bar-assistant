<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name', 'description'])]
class IngredientCategoryRequest
{
    #[OAT\Property(example: 'Spirits')]
    public string $name;
    #[OAT\Property(example: 'Category of base spirits')]
    public ?string $description = null;
}