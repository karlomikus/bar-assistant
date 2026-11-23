<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use SensitiveParameter;
use Illuminate\Support\Str;
use OpenApi\Attributes as OAT;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\User;
use Kami\Cocktail\Http\Requests\RegisterRequest as RegisterFormRequest;

#[OAT\Schema(required: ['email', 'password', 'name'])]
class RegisterRequest
{
    private function __construct(
        #[OAT\Property(example: 'admin@example.com', format: 'email', maxLength: 255)]
        public string $email,
        #[OAT\Property(example: 'Bar Tender', maxLength: 255)]
        public string $name,
        #[SensitiveParameter]
        #[OAT\Property(example: 'password', minLength: 5, maxLength: 255, format: 'password')]
        public string $password,
        public ?string $hashedPassword = null,
        public ?string $ip = null,
    ) {
    }

    public static function fromIlluminateRequest(RegisterFormRequest $request): self
    {
        /** @var array{email: string, name: string, password: string} $validated */
        $validated = $request->validated();
        $hashedPassword = Hash::make($validated['password']);

        return new self(
            email: $validated['email'],
            name: $validated['name'],
            password: $validated['password'],
            hashedPassword: $hashedPassword,
            ip: $request->ip(),
        );
    }

    public static function fromSocialiteUser(User $user): self
    {
        // Users are going to login via socialite, so we need to generate a random password
        $randomPassword = Str::random(40);
        $nickname = $user->getNickname();

        return new self(
            email: $user->getEmail() ?? '',
            name: blank($nickname) ? ($user->getName() ?? '') : (string) $nickname,
            password: $randomPassword,
            hashedPassword: Hash::make($randomPassword),
            ip: null,
        );
    }
}
