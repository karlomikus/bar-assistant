<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['type', 'message'])]
class APIError
{
    #[OAT\Property(example: 'api_error')]
    public string $type;
    #[OAT\Property(example: 'Resource record not found.')]
    public string $message;
}
