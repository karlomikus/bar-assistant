<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['data'])]
class BulkImportResponse
{
    #[OAT\Property(property: 'data', type: 'object', properties: [
        new OAT\Property(property: 'items', type: 'array', items: new OAT\Items(ref: self::class . '\\ItemRef')),
        new OAT\Property(property: 'counts', type: BulkImportCounts::class),
    ], required: ['items','counts'])]
    public array $data;

    #[OAT\Schema(schema: 'ItemRef', ref: BulkImportItem::class)]
    public mixed $_itemsRef;
}


