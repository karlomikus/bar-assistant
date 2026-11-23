<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['role_id', 'email'])]
class UserRequest
{
    #[OAT\Property(property: 'role_id', type: 'integer', enum: [1, 3, 4], example: 1)]
    public int $roleId;

    #[OAT\Property(type: 'string', format: 'email', maxLength: 255, example: 'admin@example.com')]
    public string $email;
}
