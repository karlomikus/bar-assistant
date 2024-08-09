<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI;

use OpenApi\Attributes as OAT;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class PaginateData extends OAT\JsonContent
{
    /**
     * @param class-string $className
     */
    public function __construct(string $className)
    {
        parent::__construct(properties: [
            new OAT\Property(property: 'data', type: 'array', items: new OAT\Items(ref: $className)),
            new OAT\Property(property: 'links', type: 'object', properties: [
                new OAT\Property(property: 'first', type: 'string', nullable: true),
                new OAT\Property(property: 'last', type: 'string', nullable: true),
                new OAT\Property(property: 'prev', type: 'string', nullable: true),
                new OAT\Property(property: 'next', type: 'string', nullable: true),
            ]),
            new OAT\Property(property: 'meta', type: 'object', properties: [
                new OAT\Property(property: 'current_page', type: 'integer'),
                new OAT\Property(property: 'from', type: 'integer'),
                new OAT\Property(property: 'last_page', type: 'integer'),
                new OAT\Property(property: 'links', type: 'array', items: new OAT\Items(type: 'object', properties: [
                    new OAT\Property(property: 'url', type: 'string'),
                    new OAT\Property(property: 'label', type: 'string'),
                    new OAT\Property(property: 'active', type: 'boolean'),
                ])),
                new OAT\Property(property: 'path', type: 'string'),
                new OAT\Property(property: 'per_page', type: 'integer'),
                new OAT\Property(property: 'to', type: 'integer'),
                new OAT\Property(property: 'total', type: 'integer'),
            ]),
        ]);
    }
}
