<?php

namespace Kami\Cocktail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OAuthLoginEnabled
{
    public function __construct() {}

    public function handle(Request $request, Closure $next)
    {
        if (!config('bar-assistant.oauth_login_enabled')) {
            return response()->json([
                'message' => 'OAuth login is disabled'
            ], 403);
        }

        return $next($request);
    }
}
