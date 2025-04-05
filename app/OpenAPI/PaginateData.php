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
            new OAT\Property(property: 'data', type: 'array', items: new OAT\Items(ref: $className), description: 'The data for the current page'),
            new OAT\Property(property: 'links', type: 'object', properties: [
                new OAT\Property(property: 'first', type: 'string', nullable: true, description: 'Link to the first page'),
                new OAT\Property(property: 'last', type: 'string', nullable: true, description: 'Link to the last page'),
                new OAT\Property(property: 'prev', type: 'string', nullable: true, description: 'Link to the previous page'),
                new OAT\Property(property: 'next', type: 'string', nullable: true, description: 'Link to the next page'),
            ], description: 'Links for pagination'),
            new OAT\Property(property: 'meta', type: 'object', properties: [
                new OAT\Property(property: 'current_page', type: 'integer', description: 'The current page number'),
                new OAT\Property(property: 'from', type: 'integer', description: 'The starting index of the current page'),
                new OAT\Property(property: 'last_page', type: 'integer', description: 'The last page number'),
                new OAT\Property(property: 'links', type: 'array', items: new OAT\Items(type: 'object', properties: [
                    new OAT\Property(property: 'url', type: 'string', nullable: true, description: 'The URL of the link'),
                    new OAT\Property(property: 'label', type: 'string', nullable: true, description: 'The label of the link'),
                    new OAT\Property(property: 'active', type: 'boolean', nullable: true, description: 'Whether the link is active'),
                ], description: 'Links for pagination')),
                new OAT\Property(property: 'path', type: 'string', description: 'The path of the current page'),
                new OAT\Property(property: 'per_page', type: 'integer', description: 'The number of items per page'),
                new OAT\Property(property: 'to', type: 'integer', description: 'The ending index of the current page'),
                new OAT\Property(property: 'total', type: 'integer', description: 'The total number of items'),
            ]),
        ]);
    }
}
