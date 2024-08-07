<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['id', 'name', 'description'])]
class Utensil
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'Shaker')]
    public string $name;
    #[OAT\Property(example: 'Used to shake ingredients')]
    public ?string $description = null;
}