<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['your_top_ingredients', 'top_rated_cocktails'])]
class BarTopStats
{
    /** @var array<mixed> */
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', required: ['id', 'slug', 'name', 'cocktails_count'], properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1),
        new OAT\Property(property: 'slug', type: 'string', example: 'gin'),
        new OAT\Property(property: 'name', type: 'string', example: 'Gin'),
        new OAT\Property(property: 'cocktails_count', type: 'integer', example: 1),
    ]))]
    public array $your_top_ingredients;
    /** @var array<mixed> */
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', required: ['id', 'slug', 'name', 'avg_rating', 'votes'], properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1),
        new OAT\Property(property: 'slug', type: 'string', example: 'old-fashioned'),
        new OAT\Property(property: 'name', type: 'string', example: 'Old Fashioned'),
        new OAT\Property(property: 'avg_rating', type: 'integer', example: 3),
        new OAT\Property(property: 'votes', type: 'integer', example: 42),
    ]))]
    public array $top_rated_cocktails;
}
