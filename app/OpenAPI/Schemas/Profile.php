<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Http\Resources\OauthCredentialResource;

#[OAT\Schema(required: ['id', 'name', 'email', 'is_subscribed', 'memberships', 'oauth_credentials'])]
class Profile
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'Floral')]
    public string $name;
    #[OAT\Property(example: 'example@example.com')]
    public string $email;
    #[OAT\Property(property: 'is_subscribed')]
    public bool $isSubscribed = false;
    /** @var BarMembership[] */
    #[OAT\Property()]
    public array $memberships;
    /** @var OauthCredentialResource[] */
    #[OAT\Property(property: 'oauth_credentials')]
    public array $oauthCredentials;
}
