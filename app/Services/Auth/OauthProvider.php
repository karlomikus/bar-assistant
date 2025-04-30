<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Auth;

use OpenApi\Attributes as OAT;

#[OAT\Schema(type: 'string')]
/**
 * Provides a list of supported SSO providers.
 */
enum OauthProvider: string
{
    case GitHub = 'github';
    case Google = 'google';
    case GitLab = 'gitlab';
    case Authentik = 'authentik';
    case Authelia = 'authelia';
    case Keycloak = 'keycloak';
    case PocketId = 'pocketid';

    public function getPrettyName(): string
    {
        return match ($this) {
            self::GitHub => 'GitHub',
            self::Google => 'Google',
            self::GitLab => 'GitLab',
            self::Authentik => 'Authentik',
            self::Authelia => 'Authelia',
            self::Keycloak => 'Keycloak',
            self::PocketId => 'PocketId',
        };
    }
}
