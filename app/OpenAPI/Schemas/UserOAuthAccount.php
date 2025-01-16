<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: [
    'id',
    'icon',
    'name',
    'userId',
    'createdAt',
])]
class UserOAuthAccount
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'oidc.svg')]
    public string $icon;
    #[OAT\Property(example: 'Google|Facebook|Keycloak')]
    public int $name;
    #[OAT\Property(example: '1|faaf-fawefa-ffawef-awef')]
    public int $userId;
    #[OAT\Property(example: '2022-01-01T00:00:00+00:00', format: 'date-time')]
    public string $createdAt;
}
