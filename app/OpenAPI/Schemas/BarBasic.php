<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['id', 'slug', 'name', 'subtitle'])]
class BarBasic
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'bar-name-1')]
    public string $slug;
    #[OAT\Property(example: 'Bar name')]
    public string $name;
    #[OAT\Property(example: 'Bar subtitle')]
    public string $subtitle;
}
