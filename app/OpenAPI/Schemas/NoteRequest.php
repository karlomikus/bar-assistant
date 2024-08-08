<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['note', 'resource_id', 'resource'])]
class NoteRequest
{
    #[OAT\Property(example: 'Note text')]
    public string $note;
    #[OAT\Property(property: 'resource_id', example: 1)]
    public int $resourceId;
    #[OAT\Property(example: 'cocktail')]
    public string $resource;
}
