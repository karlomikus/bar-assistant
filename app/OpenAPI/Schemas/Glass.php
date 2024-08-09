<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['id', 'name', 'description', 'cocktails_count', 'volume', 'volume_units'])]
class Glass
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'Lowball')]
    public string $name;
    #[OAT\Property(example: 'Glass for smaller cocktails')]
    public ?string $description = null;
    #[OAT\Property(property: 'cocktails_count', example: 32)]
    public int $cocktailsCount;
    #[OAT\Property(example: 120.0)]
    public ?float $volume = null;
    #[OAT\Property(property: 'volume_units', example: 'ml')]
    public ?string $volumeUnits = null;
}
