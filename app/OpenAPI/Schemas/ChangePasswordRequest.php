<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['current_password', 'password'])]
class ChangePasswordRequest
{
    public function __construct(
        #[OAT\Property(example: 'newpassword', format: 'password')]
        public ?string $password = null,
        #[OAT\Property(property: 'current_password', example: 'current', format: 'password')]
        public ?string $currentPassword = null,
    ) {
    }

    public static function fromIlluminateRequest(Request $request): self
    {
        return new self(
            password: $request->input('password'),
            currentPassword: $request->input('current_password'),
        );
    }
}
