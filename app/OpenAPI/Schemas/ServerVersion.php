<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['version', 'type', 'search_host', 'search_version'])]
class ServerVersion
{
    #[OAT\Property(example: '1.0.0')]
    public string $version;
    #[OAT\Property(example: 'production')]
    public string $type;
    #[OAT\Property(property: 'search_host', example: 'https://search.example.com')]
    public string $searchHost;
    #[OAT\Property(property: 'search_version', example: '1.2.0')]
    public string $searchVersion;
}
