<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name', 'dilution_percentage'])]
class CocktailMethodRequest
{
    #[OAT\Property(example: 'Shake')]
    public string $name;
    #[OAT\Property(property: 'dilution_percentage', example: 20)]
    public int $dilutionPercentage;
}
