<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class User
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'Bartender')]
    public string $name;
    #[OAT\Property(example: 'admin@example.com')]
    public string $email;
    #[OAT\Property(property: 'is_subscribed')]
    public bool $isSubscribed = false;
    #[OAT\Property(type: 'object', properties: [
        new OAT\Property(type: 'integer', property: 'bar_id', example: 1),
        new OAT\Property(type: 'integer', property: 'role_id', example: 1),
        new OAT\Property(type: 'integer', property: 'role_name', example: 'Admin'),
    ])]
    public array $role;
}
