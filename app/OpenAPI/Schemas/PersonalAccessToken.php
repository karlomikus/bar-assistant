<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class PersonalAccessToken
{
    #[OAT\Property(example: 'user_generated')]
    public string $name;
    /** @var string[] */
    #[OAT\Property(example: ['cocktails.read', 'cocktails.write', 'ingredients.read', 'ingredients.write'])]
    public array $abilities;
    #[OAT\Property(example: '2023-05-14T21:23:40.000000Z')]
    public string $lastUsedAt;
    #[OAT\Property(example: '2023-05-14T21:23:40.000000Z')]
    public string $createdAt;
    #[OAT\Property(example: '2023-05-14T21:23:40.000000Z')]
    public string $expiresAt;
}
