<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: [
    'id',
    'clientId',
    'type',
    'icon',
    'name',
    'authority',
    'redirectUri',
    'scope'
])]
class OAuthProvider
{
    #[OAT\Property(example: 'google|facebook|keycloak')]
    public string $id;
    #[OAT\Property(example: 'bar-assistant')]
    public string $clientId;
    #[OAT\Property(example: 'google|facebook|keycloak|oidc')]
    public string $type;
    #[OAT\Property(example: 'google.svg|facebook.svg|oidc.png')]
    public string $icon;
    #[OAT\Property(example: 'Google|Facebook|Keycloak')]
    public string $name;
    #[OAT\Property(example: 'https://example.com/auth')]
    public string $authority;
    #[OAT\Property(example: 'https://example.com/login/callback')]
    public string $redirectUri;
    #[OAT\Property(example: 'openid profile email')]
    public string $scope;

    public static function fromOAuthProvider(\Kami\Cocktail\OAuth\OAuthProvider $provider): self
    {
        $schema = new self();

        $schema->id = $provider->id;
        $schema->clientId = $provider->clientId;
        $schema->type = $provider->type;
        $schema->icon = $provider->icon;
        $schema->name = $provider->name;
        $schema->authority = $provider->authority;
        $schema->redirectUri = $provider->redirectUri;
        $schema->scope = $provider->scope;

        return $schema;
    }
}
