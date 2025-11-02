<?php

namespace Kami\Cocktail\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        Route::pattern('id', '[0-9]+');

        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(1000)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('importing', fn (Request $request) => $request->user()->hasActiveSubscription()
            ? Limit::none()
            : Limit::perMinute(2)->by($request->user()->id));

        RateLimiter::for('exports', fn (Request $request) => App::environment('production') ? Limit::perMinute(1)->by($request->user()?->id ?: $request->ip()) : Limit::none());

        RateLimiter::for('bar-optimization', fn (Request $request) => App::environment('production') ? Limit::perMinute(1, 10)->by($request->user()?->id ?: $request->ip()) : Limit::none());
    }
}
