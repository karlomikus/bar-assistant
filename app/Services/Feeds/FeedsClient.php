<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Feeds;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Laminas\Feed\Reader\Http\ClientInterface;
use Laminas\Feed\Reader\Http\Psr7ResponseDecorator;
use Kevinrob\GuzzleCache\Storage\LaravelCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;

class FeedsClient implements ClientInterface
{
    /** @inheritdoc */
    public function get($uri)
    {
        $cachingMiddleware = new CacheMiddleware(new GreedyCacheStrategy(new LaravelCacheStorage(Cache::store()), 60 * 60));

        $response = Http::withMiddleware($cachingMiddleware)->get($uri);

        return new Psr7ResponseDecorator($response->toPsrResponse());
    }
}
