<?php

namespace Kami\Cocktail\Providers;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use BarAssistant\Domain\DomainEventDispatcher;
use Kami\Cocktail\Infrastructure\DomainEventSubscriber\CleanupUserAnonymizedSubscriber;
use Kami\Cocktail\Infrastructure\DomainEventSubscriber\ClearPublicCocktailsCacheSubscriber;
use Kami\Cocktail\Infrastructure\DomainEventSubscriber\GlassUpdatedSearchReindexSubscriber;
use Kami\Cocktail\Infrastructure\DomainEventSubscriber\CocktailMethodUpdatedRecalculateAbvSubscriber;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    #[\Override]
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
                $prefix = is_string($prefix) ? $prefix : '';
                $redisCache = Redis::connection('cache');
                $foundKeys = $redisCache->keys('*' . $key);
                foreach ($foundKeys as $foundKey) {
                    $foundKey = (string) $foundKey;
                    $keyToDelete = $prefix !== '' ? Str::after($foundKey, $prefix) : $foundKey;
                    Log::debug('Clearing cache key via wildcard', ['key' => $foundKey]);
                    $redisCache->unlink($keyToDelete);
                }
            } catch (Throwable $e) {
                Log::error('Unable to clear cache with wildcard', ['message' => $e->getMessage()]);

                return;
            }
        });

        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('authentik', \SocialiteProviders\Authentik\Provider::class);
            $event->extendSocialite('authelia', \SocialiteProviders\Authelia\Provider::class);
            $event->extendSocialite('kanidm', \SocialiteProviders\Kanidm\Provider::class);
            $event->extendSocialite('keycloak', \SocialiteProviders\Keycloak\Provider::class);
            $event->extendSocialite('pocketid', \Kami\Cocktail\Services\Auth\PocketIdProvider::class);
            $event->extendSocialite('zitadel', \SocialiteProviders\Zitadel\Provider::class);
            $event->extendSocialite('oidc', \SocialiteProviders\OIDC\Provider::class);
        });

        DomainEventDispatcher::instance()->subscribe(app(GlassUpdatedSearchReindexSubscriber::class));
        DomainEventDispatcher::instance()->subscribe(app(ClearPublicCocktailsCacheSubscriber::class));
        DomainEventDispatcher::instance()->subscribe(app(CocktailMethodUpdatedRecalculateAbvSubscriber::class));
        DomainEventDispatcher::instance()->subscribe(app(CleanupUserAnonymizedSubscriber::class));

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
