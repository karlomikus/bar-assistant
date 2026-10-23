<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

final class VersionCheckService
{
    private const CACHE_KEY = 'ba:latest_version';
    private const CACHE_TTL = 60 * 60;
    private const CACHE_TTL_ON_FAILURE = 60 * 15;

    public function getLatestVersion(): ?string
    {
        // Wrapped in an array so a cached failure (null) is distinguishable from a cache miss
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && array_key_exists('version', $cached)) {
            return is_string($cached['version']) ? $cached['version'] : null;
        }

        try {
            $response = Http::get('https://api.github.com/repos/karlomikus/bar-assistant/releases/latest')->throw();
        } catch (Throwable) {
            Cache::put(self::CACHE_KEY, ['version' => null], self::CACHE_TTL_ON_FAILURE);

            return null;
        }

        /** @var string|null $tagName */
        $tagName = $response->json('tag_name');
        $version = (string) $tagName;

        Cache::put(self::CACHE_KEY, ['version' => $version], self::CACHE_TTL);

        return $version;
    }

    public function isLatest(?string $latest, ?string $current): bool
    {
        if ($latest === null || $current === null) {
            return true;
        }

        if ($current === 'develop' || $current === 'cloud') {
            return true;
        }

        if (str_starts_with($current, 'v')) {
            $current = substr($current, 1);
        }

        if (str_starts_with($latest, 'v')) {
            $latest = substr($latest, 1);
        }

        return version_compare($current, $latest, '>=');
    }
}
