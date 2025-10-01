<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['total','created','skipped','overwritten','failed'])]
class BulkImportCounts
{
    #[OAT\Property(type: 'integer', example: 10)]
    public int $total;

    #[OAT\Property(type: 'integer', example: 6)]
    public int $created;

    #[OAT\Property(type: 'integer', example: 2)]
    public int $skipped;

    #[OAT\Property(type: 'integer', example: 1)]
    public int $overwritten;

    #[OAT\Property(type: 'integer', example: 1)]
    public int $failed;
}


