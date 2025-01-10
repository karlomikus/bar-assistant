<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: [
    'allowRegistration',
    'localLoginEnabled',
    'oauthLoginEnabled',
    'oauthLoginSelfRegistrationEnabled',
    'oauthProviders'
])]
class AuthConfig
{
    #[OAT\Property(example: 'true|false')]
    public bool $allowRegistration;
    #[OAT\Property(example: 'true|false')]
    public bool $localLoginEnabled;
    #[OAT\Property(example: 'true|false')]
    public bool $oauthLoginEnabled;
    #[OAT\Property(example: 'true|false')]
    public bool $oauthLoginSelfRegistrationEnabled;
    /** @var array<class-string<OAuthProvider>> */
    #[OAT\Property(type: 'array', items: new OAT\Items(type: OAuthProvider::class))]
    public array $oauthProviders = [];
}
