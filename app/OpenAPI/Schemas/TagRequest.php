<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name'])]
class TagRequest
{
    #[OAT\Property(example: 'Floral')]
    public string $name;
}
