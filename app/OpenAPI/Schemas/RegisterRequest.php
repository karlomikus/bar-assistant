<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\User;

#[OAT\Schema(required: ['email', 'password', 'name'])]
class RegisterRequest
{
    private function __construct(
        #[OAT\Property(example: 'admin@example.com')]
        public string $email,
        #[OAT\Property(example: 'Bar Tender')]
        public string $name,
        #[OAT\Property(example: 'password', minLength: 5, format: 'password')]
        public string $password,
        public ?string $hashedPassword = null,
    ) {
    }

    public static function fromIlluminateRequest(Request $request): self
    {
        $hashedPassword = Hash::make($request->password);

        return new self(
            email: $request->email,
            name: $request->name,
            password: $request->password,
            hashedPassword: $hashedPassword,
        );
    }

    public static function fromSocialiteUser(User $user): self
    {
        // Users are going to login via socialite, so we need to generate a random password
        $randomPassword = Str::random(40);

        return new self(
            email: $user->getEmail(),
            name: blank($user->getNickname()) ? $user->getName() : $user->getNickname(),
            password: $randomPassword,
            hashedPassword: Hash::make($randomPassword),
        );
    }
}
