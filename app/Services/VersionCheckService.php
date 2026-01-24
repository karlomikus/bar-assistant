<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Illuminate\Support\Facades\Http;

final class VersionCheckService
{
    public function getLatestVersion(): ?string
    {
        try {
            /** TODO: remove after fixed in laravel */
            /** @phpstan-ignore-next-line */
            $response = Http::get('https://api.github.com/repos/karlomikus/bar-assistant/releases/latest')->throw();
        } catch (Throwable) {
            return null;
        }

        return $response->json('tag_name') ?? null;
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
