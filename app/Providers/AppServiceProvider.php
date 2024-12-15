<?php

namespace Kami\Cocktail\Providers;

use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (DB::getDriverName() === 'sqlite') {
            try {
                DB::statement('
                    PRAGMA temp_store = memory;
                    PRAGMA cache_size = -20000;
                    PRAGMA mmap_size = 2147483648;
                ');
            } catch (Throwable $e) {
                Log::warning('Unable to connect to DB setup PRAGMAs');
            }
        }
    }
}
