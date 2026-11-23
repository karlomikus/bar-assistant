<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use UnexpectedValueException;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\Http\Requests\UpdateUserRequest;

#[OAT\Schema(required: ['name', 'email'])]
class ProfileRequest
{
    public function __construct(
        #[OAT\Property(type: 'string', maxLength: 255, example: 'Bar Tender')]
        public string $name,
        #[OAT\Property(type: 'string', format: 'email', maxLength: 255, example: 'new@email.com')]
        public string $email,
        #[OAT\Property()]
        public ?ProfileSettings $settings = null,
    ) {
    }

    public static function fromIlluminateRequest(UpdateUserRequest $request): self
    {
        $validated = $request->validated();
        $inputSettings = $validated['settings'] ?? null;
        $settings = null;

        if (is_array($inputSettings)) {
            $settings = new ProfileSettings();
            $settings->language = self::nullableString($inputSettings['language'] ?? null);
            $settings->theme = self::nullableString($inputSettings['theme'] ?? null);
        }

        return new self(
            name: self::requiredString($validated['name'] ?? null),
            email: self::requiredString($validated['email'] ?? null),
            settings: $settings,
        );
    }

    private static function requiredString(mixed $value): string
    {
        if (! is_string($value)) {
            throw new UnexpectedValueException('Validated profile fields must be strings.');
        }

        return $value;
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
