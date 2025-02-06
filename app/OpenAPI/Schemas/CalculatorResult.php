<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['inputs', 'results'])]
class CalculatorResult
{
    /** @var array<string, string> */
    #[OAT\Property(type: 'object', additionalProperties: new OAT\AdditionalProperties(type: 'string'))]
    public array $inputs = [];
    /** @var array<string, string> */
    #[OAT\Property(type: 'object', additionalProperties: new OAT\AdditionalProperties(type: 'string'))]
    public array $results = [];
}
