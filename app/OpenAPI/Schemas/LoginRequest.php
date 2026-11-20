<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['email', 'password'])]
class LoginRequest
{
    #[OAT\Property(example: 'admin@example.com', format: 'email', maxLength: 255)]
    public string $email;
    #[OAT\Property(example: 'password', format: 'password', maxLength: 255)]
    public string $password;
    #[OAT\Property(example: 'My device', property: 'token_name', maxLength: 255)]
    public ?string $tokenName = null;
}
