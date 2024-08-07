<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['id', 'name', 'dilution_percentage', 'cocktails_count'])]
class CocktailMethod
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'Shake')]
    public string $name;
    #[OAT\Property(property: 'dilution_percentage', example: 20)]
    public int $dilutionPercentage;
    #[OAT\Property(property: 'cocktails_count', example: 32)]
    public int $cocktailsCount;
}