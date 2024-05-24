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
        $barId = $request->get('bar_id', null);

        if (!$barId) {
            abort(400, sprintf("Missing required '%s' parameter while requesting '%s'", 'bar_id', $request->path()));
        }

        $bar = Cache::remember('ba:bar:' . $barId, 60 * 60 * 24, function () use ($barId) {
            return Bar::findOrFail($barId);
        });

        if ($request->user()->cannot('access', $bar)) {
            abort(403);
        }

        app()->singleton(BarContext::class, function () use ($bar) {
            return new BarContext($bar);
        });

        return $next($request);
    }
}
