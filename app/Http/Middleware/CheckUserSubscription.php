<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && !$request->user()->hasActiveSubscription()) {
            return response()->json([
                'error' => 'api_error',
                'message' => 'You can not do that on your current subscription plan!'
            ], 400);
        }

        return $next($request);
    }
}
