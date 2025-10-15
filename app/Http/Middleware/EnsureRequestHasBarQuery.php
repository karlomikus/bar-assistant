<?php

namespace Kami\Cocktail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kami\Cocktail\BarContext;
use Kami\Cocktail\Models\Bar;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequestHasBarQuery
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $barId = $request->header('Bar-Assistant-Bar-Id', null);

        if (!$barId) {
            abort(400, sprintf("Missing required bar reference while requesting '%s'. Use 'Bar-Assistant-Bar-Id' header to specify bar id.", $request->path()));
        }

        $bar = Cache::remember('ba:bar:' . $barId, 60 * 60 * 24, fn() => Bar::findOrFail($barId));

        if ($request->user()->cannot('access', $bar)) {
            abort(403);
        }

        app()->singleton(BarContext::class, fn() => new BarContext($bar));

        return $next($request);
    }
}
