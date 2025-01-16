<?php

namespace Kami\Cocktail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\OAuth\OAuthService;
use Illuminate\Support\Facades\Auth;
use Kami\Cocktail\OAuth\OAuthCookie;
use Kami\Cocktail\OAuth\OAuthUtils;

class OAuthOrSanctum
{
    public function __construct() {}

    public function handle(Request $request, Closure $next)
    {
        if ($request->is('api/oauth/login')) {
            return $next($request);
        }

        try {
            if (!OAuthUtils::isOAuthRequest($request)) {
                Log::debug('Logging in with Sanctum...');
                if (Auth::guard('sanctum')->check()) {
                    $user = Auth::guard('sanctum')->user();
                    Auth::setUser($user);
                    return $next($request);
                }

                throw new \Exception('Local login failed');
            }

            Log::debug('Logging in with OAuth...');

            if (!config('bar-assistant.oauth_login_enabled')) {
                throw new \Exception('OAuth login is disabled');
            }

            $providerId = $request->cookie(OAuthCookie::OAUTH_PROVIDER_ID);
            $accessToken = $request->cookie(OAuthCookie::OAUTH_ACCESS_TOKEN);
            $refreshToken = $request->cookie(OAuthCookie::OAUTH_REFRESH_TOKEN);
            $providers = config('bar-assistant.oauth_login_providers');

            if (!$providerId) {
                throw new \Exception('Provider ID is missing');
            }

            $providerConfig = collect($providers)->first(fn($provider) => $provider->id === $providerId);

            if (!isset($providerConfig)) {
                throw new \Exception('Provider not found');
            }

            $oauthService = new OAuthService($providerConfig);
            $user = $oauthService->getUser($accessToken, $refreshToken);
            Auth::setUser($user);

            return $next($request);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return OAuthUtils::forgetOAuthCookies(response()->json(['error' => 'login failed'], Response::HTTP_UNAUTHORIZED));
        }
    }
}
