<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class CacheService
{
    public const SIMILAR_COCKTAILS = 'api:cocktails.%s.similar';

    public const TAG_INGREDIENT_SHOW = 'ingredients.show.%s';

    /**
     * This is not the best approach, but instead of trying to make
     * everything dynamic and configurable, for a few routes that we cache
     * doing cache logic procedurally is easier.
     *
     * @template TCacheValue
     *
     * @param \Closure(): TCacheValue $callback
     * @return TCacheValue
     */
    public function cacheRouteResponse(Request $request, int $ttl, Closure $callback): mixed
    {
        $name = (string) $request->route()->getName();
        $params = $this->serializeParameters($request->route()->parameters());
        $userId = (string) $request->user()->id;

        if ($name === 'ingredients.show') {
            $tags = [sprintf(self::TAG_INGREDIENT_SHOW, $params)];
            $key = implode('.', [$name, $params, $userId]);

            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        throw new \Exception('No cache config for route ' . $name);
    }

    /**
     * @param array<mixed> $parameters
     */
    private function serializeParameters(array $parameters): string
    {
        return implode('-', $parameters);
    }
}
