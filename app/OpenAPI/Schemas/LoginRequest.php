<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['email', 'password'])]
class LoginRequest
{
    #[OAT\Property(example: 'admin@example.com')]
    public string $email;
    #[OAT\Property(example: 'password', format: 'password')]
    public string $password;
    #[OAT\Property(example: 'My device', property: 'token_name')]
    public ?string $tokenName = null;
}
