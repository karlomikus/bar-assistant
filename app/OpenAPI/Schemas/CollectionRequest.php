<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name'])]
class CollectionRequest
{
    #[OAT\Property(example: 'My summer cocktails', description: 'Name of the collection')]
    public string $name;
    #[OAT\Property(example: 'Refreshing cocktails for a hot summer day.', description: 'A short description of the collection')]
    public ?string $description = null;
    #[OAT\Property(example: false, property: 'is_bar_shared', description: 'Whether the collection should be shared with the bar. Shared collections are visible to all bar members. Default `false`.')]
    public bool $isBarShared = false;
    /** @var int[] */
    #[OAT\Property(example: [1, 2, 3], description: 'List of cocktail ids that belong to this collection', items: new OAT\Items(type: 'integer'))]
    public array $cocktails = [];
}
