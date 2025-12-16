<?php

namespace Kami\Cocktail\Providers;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $bindings = [
        \Laminas\Feed\Reader\Http\ClientInterface::class => \Kami\Cocktail\Services\Feeds\FeedsClient::class,
    ];

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
        Cache::macro('forgetWildcardRedis', function (string $key) {
            try {
                $prefix = config('database.redis.options.prefix');
                $redisCache = Redis::connection('cache');
                $foundKeys = $redisCache->keys('*' . $key);
                foreach ($foundKeys as $foundKey) {
                    $keyToDelete = $prefix ? Str::after($foundKey, $prefix) : $foundKey;
                    Log::debug('Clearing cache key via wildcard', ['key' => $foundKey]);
                    $redisCache->unlink($keyToDelete);
                }
            } catch (Throwable $e) {
                Log::error('Unable to clear cache with wildcard', ['message' => $e->getMessage()]);

                return;
            }
        });

        if (config('bar-assistant.scraping_client.proxy') !== null) {
            $options = [
                'proxy' => config('bar-assistant.scraping_client.proxy'),
            ];

            if (config('bar-assistant.scraping_client.cert')) {
                $options['verify'] = config('bar-assistant.scraping_client.cert');
            }

            Http::globalOptions($options);
        }

        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('authentik', \SocialiteProviders\Authentik\Provider::class);
            $event->extendSocialite('authelia', \SocialiteProviders\Authelia\Provider::class);
            $event->extendSocialite('keycloak', \SocialiteProviders\Keycloak\Provider::class);
            $event->extendSocialite('pocketid', \Kami\Cocktail\Services\Auth\PocketIdProvider::class);
            $event->extendSocialite('zitadel', \SocialiteProviders\Zitadel\Provider::class);
        });

        if (DB::getDriverName() === 'sqlite') {
            try {
                DB::statement('
                    PRAGMA temp_store = memory;
                    PRAGMA cache_size = -20000;
                    PRAGMA mmap_size = 2147483648;
                ');
            } catch (Throwable) {
                Log::warning('Unable to set SQLite performance pragmas');
            }
        }
    }
}
