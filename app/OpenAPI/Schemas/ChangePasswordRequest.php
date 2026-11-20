<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use UnexpectedValueException;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\Http\Requests\UpdatePasswordRequest;

#[OAT\Schema(required: ['current_password', 'new_password', 'new_password_confirmation'])]
class ChangePasswordRequest
{
    public function __construct(
        #[OAT\Property(property: 'new_password', example: 'newpassword', format: 'password', minLength: 5, maxLength: 255)]
        public string $password,
        #[OAT\Property(property: 'current_password', example: 'current', format: 'password', maxLength: 255)]
        public string $currentPassword,
        #[OAT\Property(property: 'new_password_confirmation', example: 'newpassword', format: 'password', maxLength: 255)]
        public string $passwordConfirmation,
    ) {
    }

    public static function fromIlluminateRequest(UpdatePasswordRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            password: self::requiredString($validated['new_password'] ?? null),
            currentPassword: self::requiredString($validated['current_password'] ?? null),
            passwordConfirmation: self::requiredString($validated['new_password_confirmation'] ?? null),
        );
    }

    private static function requiredString(mixed $value): string
    {
        if (! is_string($value)) {
            throw new UnexpectedValueException('Validated password fields must be strings.');
        }

        return $value;
    }
}
