<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Middleware;

use Closure;
use Throwable;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\Metrics\ApiRequestDuration;

class TracksRequestMetric
{
    public function __construct(private readonly CollectorRegistry $registry)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = floatval($request->server('REQUEST_TIME_FLOAT'));

        $response = $next($request);

        try {
            $metric = new ApiRequestDuration($this->registry);
            $metric(microtime(true) - $startTime, (string) $request->route()->uri(), $request->method(), $response->getStatusCode());
        } catch (Throwable $e) {
            Log::error('Unable to register metric: ' . ApiRequestDuration::class . '. Error: ' . $e->getMessage());
        }

        return $response;
    }
}
