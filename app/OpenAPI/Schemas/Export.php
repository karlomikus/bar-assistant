<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class Export
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'cocktails.csv')]
    public string $filename;
    #[OAT\Property(example: '2023-05-14T21:23:40.000000Z', format: 'date-time', property: 'created_at')]
    public string $createdAt;
    #[OAT\Property(example: 'Bar name', property: 'bar_name')]
    public string $barName;
    #[OAT\Property(example: true, property: 'is_done')]
    public bool $isDone;
}
