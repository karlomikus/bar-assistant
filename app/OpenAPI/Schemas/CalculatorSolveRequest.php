<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['inputs'])]
readonly class CalculatorSolveRequest
{
    /**
     * @param array<string, string> $inputs
     */
    public function __construct(
        #[OAT\Property(type: 'object', additionalProperties: new OAT\AdditionalProperties(type: 'string'))]
        public array $inputs,
    ) {
    }

    /**
     * @param array<string, mixed> $source
     */
    public static function fromArray(array $source): self
    {
        return new self(
            $source['inputs'],
        );
    }
}
