<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class PersonalAccessToken
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'user_generated')]
    public string $name;
    /** @var string[] */
    #[OAT\Property(example: ['cocktails.read', 'cocktails.write', 'ingredients.read', 'ingredients.write'])]
    public array $abilities;
    #[OAT\Property(example: '2023-05-14T21:23:40.000000Z', property: 'last_used_at')]
    public string $lastUsedAt;
    #[OAT\Property(example: '2023-05-14T21:23:40.000000Z', property: 'created_at')]
    public string $createdAt;
    #[OAT\Property(example: '2023-05-14T21:23:40.000000Z', property: 'expires_at')]
    public string $expiresAt;
}
