<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['main_category_ingredient_distribution'])]
class BarIngredientDistribution
{
    /** @var array<mixed> */
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', required: ['id', 'slug', 'name', 'ingredients_count'], properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1),
        new OAT\Property(property: 'slug', type: 'string', example: 'spirits'),
        new OAT\Property(property: 'name', type: 'string', example: 'Spirits'),
        new OAT\Property(property: 'ingredients_count', type: 'integer', example: 12),
    ]))]
    public array $main_category_ingredient_distribution;
}
