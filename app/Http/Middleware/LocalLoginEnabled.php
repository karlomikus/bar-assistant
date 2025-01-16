<?php

namespace Kami\Cocktail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LocalLoginEnabled
{
    public function __construct() {}

    public function handle(Request $request, Closure $next)
    {
        if (!config('bar-assistant.local_login_enabled')) {
            return response()->json([
                'message' => 'Local login is disabled'
            ], 403);
        }

        return $next($request);
    }
}
