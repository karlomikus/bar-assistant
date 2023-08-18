<?php

namespace Kami\Cocktail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Http\BarContext;
use Symfony\Component\HttpFoundation\Response;

class HasBarContext
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
            abort(400, 'Missing required \'bar_id\' parameter');
        }

        $bar = Bar::findOrFail($barId);

        app()->singleton(BarContext::class, function () use ($bar) {
            return new BarContext($bar);
        });

        return $next($request);
    }
}
