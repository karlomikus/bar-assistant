<?php

declare(strict_types=1);

namespace Kami\Cocktail\Search;

class NullActions implements SearchActionsContract
{
    public function getPublicApiKey(bool $isDemo = false): ?string
    {
        return null;
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function getVersion(): ?string
    {
        return null;
    }

    public function updateIndexSettings(): void
    {
    }
}
