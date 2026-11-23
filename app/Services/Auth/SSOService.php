<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Auth;

use RuntimeException;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\OauthCredential;
use Kami\Cocktail\OpenAPI\Schemas\RegisterRequest;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final readonly class SSOService
{
    public function __construct(private RegisterUserService $registerUserService)
    {
    }

    public function findOrCreateCredential(SocialiteUser $socialiteUser, OauthProvider $provider): OauthCredential
    {
        $providerId = $socialiteUser->getId();
        if (blank($providerId)) {
            throw new RuntimeException('SSO provider did not return a user ID.');
        }

        $existingCredentials = OauthCredential::where('provider', $provider->value)
            ->where('provider_id', $providerId)
            ->first();

        if ($existingCredentials !== null) {
            return $existingCredentials;
        }

        $email = $socialiteUser->getEmail();
        if (blank($email)) {
            throw new RuntimeException('SSO provider did not return an email address.');
        }

        $user = User::where('email', $email)->first();
        if ($user !== null) {
            $credentials = new OauthCredential();
            $credentials->provider = $provider->value;
            $credentials->provider_id = $providerId;
            $credentials->user_id = $user->id;
            $credentials->save();
        } else {
            $registerInfo = RegisterRequest::fromSocialiteUser($socialiteUser);

            $user = $this->registerUserService->register($registerInfo);
            $credentials = new OauthCredential();
            $credentials->provider = $provider->value;
            $credentials->provider_id = $providerId;
            $credentials->user_id = $user->id;
            $credentials->save();
        }

        return $credentials;
    }
}
