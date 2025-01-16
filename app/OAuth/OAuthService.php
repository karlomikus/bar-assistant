<?php

declare(strict_types=1);

namespace Kami\Cocktail\OAuth;

use League\OAuth2\Client\Provider\GenericProvider;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\UserOAuthAccount;
use League\OAuth2\Client\Token\AccessToken;
use Illuminate\Support\Facades\Log;

class OAuthService
{
    protected $config;
    protected $provider;

    public function __construct(OAuthProvider $provider)
    {
        $this->config = $provider;

        $this->provider = new GenericProvider([
            'clientId'                => $provider->clientId,
            'clientSecret'            => $provider->clientSecret,
            'redirectUri'             => $provider->redirectUri,
            'urlAuthorize'            => $provider->authorizationEndpoint,
            'urlAccessToken'          => $provider->tokenEndpoint,
            'urlResourceOwnerDetails' => $provider->userInfoEndpoint,
            'scope'                   => $provider->scope,
            'responseResourceOwnerId' => $provider->userIdKey,
        ]);
    }

    public function getAccessToken(string $code, string $codeVerifier)
    {
        $this->provider->setPkceCode($codeVerifier);
        $accessToken = $this->provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);
        return $accessToken;
    }

    public function refreshAccessToken(string $refreshToken)
    {
        return $this->provider->getAccessToken('refresh_token', [
            'refresh_token' => $refreshToken,
        ]);
    }

    public function userInfo($accessToken)
    {
        return $this->provider->getResourceOwner($accessToken)->toArray();
    }

    public function handleUserLogin($providerId, $accessToken)
    {
        $userInfo = $this->userInfo($accessToken);
        $email = $userInfo['email'] ?? null;

        if (!$email) {
            throw new \Exception('Email not provided by provider');
        }

        $providerUserId = $userInfo[$this->config->userIdKey];

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $userInfo[$this->config->nameKey],
                'password' => bcrypt(uniqid()), // Password is not used for OAuth users
                'email_verified_at' => now(),   // Email is always verified with OAuth
            ]
        );

        UserOAuthAccount::updateOrCreate([
            'provider_id'      => $providerId,
            'user_id' => $user->id,
        ], [
            'provider_user_id' => $providerUserId,
        ]);

        return $user;
    }

    public function getUser(string $accessToken, string $refreshToken): User
    {
        $providerId = $this->config->id;
        $accessTokenObject = new AccessToken(['access_token' => $accessToken]);
        $userInfo = null;

        try {
            $userInfo = $this->userInfo($accessTokenObject);
        } catch (\Exception $e) {
            Log::error('Failed to fetch user info with current token, trying to refresh token', ['exception' => $e]);
        }

        if (!$userInfo) {
            $newToken = $this->refreshAccessToken($refreshToken);
            OAuthUtils::setOAuthCookies($providerId, $newToken);
            $userInfo = $this->userInfo($newToken);
        }

        $providerUserId = $userInfo[$this->config->userIdKey] ?? null;

        if (!$providerUserId) {
            throw new \Exception('Failed to retrieve user ID from provider');
        }

        $userOAuthAccount = UserOAuthAccount::with('user')
            ->where('provider_id', $providerId)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if (!$userOAuthAccount || !$userOAuthAccount->user) {
            throw new \Exception('User not found');
        }

        return $userOAuthAccount->user;
    }
}
