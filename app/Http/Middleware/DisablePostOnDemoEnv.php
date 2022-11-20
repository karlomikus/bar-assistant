<?php

namespace Kami\Cocktail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class DisablePostOnDemoEnv
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $allowedDemoPostRoutes = [
            'auth.login',
            'auth.logout'
        ];

        if (App::environment('demo') && !$request->isMethodSafe() && !$request->routeIs($allowedDemoPostRoutes)) {
            return response()->json([
                'error' => 'api_error',
                'message' => 'Disabled on current environment.'
            ], 405);
        }

        return $next($request);
    }
}
