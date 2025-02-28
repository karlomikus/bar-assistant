<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Auth;

use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\OauthCredential;
use Kami\Cocktail\Services\Auth\RegisterUserService;
use Kami\Cocktail\OpenAPI\Schemas\RegisterRequest;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final class SSOService
{
    public function __construct(private readonly RegisterUserService $registerUserService)
    {
    }

    public function findOrCreateCredential(SocialiteUser $socialiteUser, OauthProvider $provider): OauthCredential
    {
        $existingCredentials = OauthCredential::where('provider', $provider->value)
            ->where('provider_id', $socialiteUser->getId())
            ->first();

        if ($existingCredentials !== null) {
            return $existingCredentials;
        }

        $user = User::where('email', $socialiteUser->getEmail())->first();
        if ($user !== null) {
            $credentials = new OauthCredential();
            $credentials->provider = $provider->value;
            $credentials->provider_id = $socialiteUser->getId();
            $credentials->user_id = $user->id;
            $credentials->save();
        } else {
            $registerInfo = RegisterRequest::fromSocialiteUser($socialiteUser);

            $user = $this->registerUserService->register($registerInfo);
            $credentials = new OauthCredential();
            $credentials->provider = $provider->value;
            $credentials->provider_id = $socialiteUser->getId();
            $credentials->user_id = $user->id;
            $credentials->save();
        }

        return $credentials;
    }
}
