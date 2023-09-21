<?php

declare(strict_types=1);

namespace Kami\Cocktail\Search;

interface SearchActionsContract
{
    public function getBarSearchApiKey(int $barId): ?string;

    public function isAvailable(): bool;

    public function getVersion(): ?string;

    public function getHost(): ?string;

    public function updateIndexSettings(): void;
}
