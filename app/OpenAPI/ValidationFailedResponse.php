<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI\Schemas\ValidationError;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ValidationFailedResponse extends OAT\Response
{
    public function __construct()
    {
        parent::__construct(response: 422, description: 'Request validation failed.', content: [
            new OAT\JsonContent(ref: ValidationError::class),
        ]);
    }
}
