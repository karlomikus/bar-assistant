<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['role_id', 'email', 'name', 'password'])]
class UserRequest
{
    #[OAT\Property(property: 'role_id', example: 1)]
    public int $roleId;
    #[OAT\Property(example: 'admin@example.com')]
    public string $email;
    #[OAT\Property(example: 'Bar Tender')]
    public string $name;
    #[OAT\Property(example: 'password', format: 'password')]
    public string $password;
}
