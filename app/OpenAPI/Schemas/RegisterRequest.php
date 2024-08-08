<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['email', 'password', 'name'])]
class RegisterRequest
{
    #[OAT\Property(example: 'admin@example.com')]
    public string $email;
    #[OAT\Property(example: 'Bar Tender')]
    public string $name;
    #[OAT\Property(example: 'password', minLength: 5)]
    public string $password;
}
