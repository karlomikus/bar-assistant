<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Kami\Cocktail\Services\CacheService;

class CacheRouteResponse
{
    public function __construct(private readonly CacheService $responseCacheService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethodCacheable()) {
            return $next($request);
        }

        return $this->responseCacheService->cacheRouteResponse($request, 60 * 60 * 24, fn () => $next($request));
    }
}
