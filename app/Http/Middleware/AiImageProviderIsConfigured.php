<?php

namespace Kami\Cocktail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prism\Prism\Enums\Provider;
use Symfony\Component\HttpFoundation\Response;

class AiImageProviderIsConfigured
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $provider = Provider::tryFrom(config('bar-assistant.ai.image.provider'));
        if ($provider === null) {
            abort(400, 'Image AI provider not configured or not supported');
        }

        return $next($request);
    }
}
