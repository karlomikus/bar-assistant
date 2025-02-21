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
     * @var array<string, array<string>>
     */
    public array $routeTags = [
        'route:ingredients.show' => [self::TAG_INGREDIENT_SHOW],
    ];

    public function getRouteKey(Request $request): ?string
    {
        $name = (string) $request->route()->getName();

        if (!$this->isCacheable($request)) {
            return null;
        }

        $params = $this->serializeParameters($request->route()->parameters());
        $userId = (string) $request->user()->id;

        $key = implode('.', [$name, $params, $userId]);

        return $key;
    }

    public function isCacheable(Request $request): bool
    {
        return array_key_exists($this->getKey($request), $this->routeTags);
    }

    /**
     * @return array<string>
     */
    public function getRouteTags(Request $request): array
    {
        $tagTemplates = $this->routeTags[$this->getKey($request)] ?? [];

        return array_map(fn ($tagTemplate) => sprintf($tagTemplate, $this->serializeParameters($request->route()->parameters())), $tagTemplates);
    }

    /**
     * @template TCacheValue
     *
     * @param \Closure(): TCacheValue $callback
     * @return TCacheValue
     */
    public function cacheRouteResponse(Request $request, int $ttl, Closure $callback): mixed
    {
        $key = $this->getRouteKey($request);
        $tags = $this->getRouteTags($request);

        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    /**
     * @param array<mixed> $parameters
     */
    private function serializeParameters(array $parameters): string
    {
        return implode('-', $parameters);
    }

    private function getKey(Request $request): string
    {
        return 'route:' . $request->route()->getName();
    }
}
