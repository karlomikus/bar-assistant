<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['errors', 'message'])]
class ValidationError
{
    #[OAT\Property(example: 'The cocktail name must be a string. (and 2 more errors)')]
    public string $message;
    /** @var array<mixed> */
    #[OAT\Property(type: 'object', additionalProperties: new OAT\AdditionalProperties(type: 'array', items: new OAT\Items(type: 'string')))]
    public array $errors;
}
