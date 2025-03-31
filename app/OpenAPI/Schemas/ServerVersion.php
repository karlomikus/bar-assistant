<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['version', 'type', 'search_host', 'search_version', 'latest_version', 'is_latest', 'type', 'is_feeds_enabled', 'is_password_login_enabled'])]
class ServerVersion
{
    #[OAT\Property(example: '1.0.0', description: 'Version of the server')]
    public string $version;
    #[OAT\Property(example: '3.1.0', description: 'Latest version available on GitHub')]
    public string $latest_version;
    #[OAT\Property(example: true, description: 'Whether the server is running the latest version')]
    public bool $is_latest;
    #[OAT\Property(example: 'production', description: 'Environment the server is running in')]
    public string $type;
    #[OAT\Property(property: 'search_host', example: 'https://search.example.com')]
    public string $searchHost;
    #[OAT\Property(property: 'search_version', example: '1.2.0', description: 'Version of the search engine')]
    public string $searchVersion;
    #[OAT\Property(property: 'is_feeds_enabled', example: true, description: 'Whether feeds are enabled')]
    public bool $isFeedsEnabled;
    #[OAT\Property(property: 'is_password_login_enabled', example: true, description: 'Whether password login is enabled')]
    public bool $isPasswordLoginEnabled;
}
