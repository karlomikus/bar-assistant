<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;

class CheckMetricsAccess
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
        // Allow all local requests
        if (App::environment('local') && IpUtils::isPrivateIp($request->ip())) {
            return $next($request);
        }

        $whitelist = array_filter(config('bar-assistant.metrics.allowed_ips', []), fn($ip) => $ip !== '');

        if (count($whitelist) === 0) {
            abort(404);
        }

        if (IpUtils::checkIp($request->ip(), $whitelist) || in_array('*', $whitelist)) {
            return $next($request);
        }

        Log::warning('Denied IP address ' . $request->ip() . ' metrics endpoint access');

        abort(403);
    }
}
