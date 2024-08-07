<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['id', 'name'])]
class UserBasic
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'Bartender')]
    public string $name;
}