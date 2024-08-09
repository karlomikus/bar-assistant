<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI;

use OpenApi\Attributes as OAT;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class WrapObjectWithData extends OAT\JsonContent
{
    /**
     * @param class-string $className
     */
    public function __construct(string $className)
    {
        parent::__construct(properties: [new OAT\Property(property: 'data', type: 'object', ref: $className)]);
    }
}
