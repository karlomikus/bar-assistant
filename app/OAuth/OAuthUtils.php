<?php

declare(strict_types=1);

namespace Kami\Cocktail\OAuth;

use Illuminate\Http\Request;
use Kami\Cocktail\OAuth\OAuthCookie;
use Illuminate\Support\Facades\Cookie;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Illuminate\Http\JsonResponse;

class OAuthUtils
{
    public static function isOAuthRequest(Request $request): bool
    {
        $providerId = $request->cookie(OAuthCookie::OAUTH_PROVIDER_ID);
        $accessToken = $request->cookie(OAuthCookie::OAUTH_ACCESS_TOKEN);
        $refreshToken = $request->cookie(OAuthCookie::OAUTH_REFRESH_TOKEN);

        return $providerId && $accessToken && $refreshToken;
    }

    public static function forgetOAuthCookies(JsonResponse $response)
    {
        return $response
            ->withCookie(Cookie::forget(OAuthCookie::OAUTH_PROVIDER_ID))
            ->withCookie(Cookie::forget(OAuthCookie::OAUTH_ACCESS_TOKEN))
            ->withCookie(Cookie::forget(OAuthCookie::OAUTH_REFRESH_TOKEN));
    }

    public static function setOAuthCookies(string $providerId, AccessTokenInterface $accessToken)
    {
        Cookie::queue(cookie(
            OAuthCookie::OAUTH_PROVIDER_ID,
            $providerId,
            60 * 24 * 30,
            '/',
            null,
            false,
            true
        ));

        Cookie::queue(cookie(
            OAuthCookie::OAUTH_ACCESS_TOKEN,
            $accessToken->getToken(),
            $accessToken->getExpires() / 60,
            '/',
            null,
            false,
            true
        ));

        Cookie::queue(cookie(
            OAuthCookie::OAUTH_REFRESH_TOKEN,
            $accessToken->getRefreshToken(),
            60 * 24 * 30,
            '/',
            null,
            false,
            true
        ));
    }
}
