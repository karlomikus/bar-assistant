<?php

namespace Kami\Cocktail\Http\Middleware;

use Kami\Cocktail\OAuth\OAuthUtils;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility as SanctumCheckForAnyAbility;
use Illuminate\Http\Request;
use Closure;

class CheckForAnyAbility
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param mixed ...$abilities
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Laravel\Sanctum\Exceptions\MissingAbilityException
     */
    public function handle(Request $request, Closure $next, ...$abilities)
    {
        if (OAuthUtils::isOAuthRequest($request)) {
            return $next($request);
        }

        $sanctumMiddleware = app(SanctumCheckForAnyAbility::class);
        return $sanctumMiddleware->handle($request, $next, ...$abilities);
    }
}
