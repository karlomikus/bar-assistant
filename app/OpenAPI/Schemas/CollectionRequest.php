<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name'])]
class CollectionRequest
{
    #[OAT\Property(example: 'Collection name')]
    public string $name;
    #[OAT\Property(example: 'Collection description')]
    public ?string $description = null;
    #[OAT\Property(property: 'is_bar_shared')]
    public bool $isBarShared = false;
    /** @var int[] */
    #[OAT\Property()]
    public array $cocktails = [];
}
