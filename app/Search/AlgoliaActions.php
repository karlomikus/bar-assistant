<?php

declare(strict_types=1);

namespace Kami\Cocktail\Search;

use Throwable;
use Laravel\Scout\Engines\AlgoliaEngine;

class AlgoliaActions implements SearchActionsContract
{
    public function __construct(private AlgoliaEngine $client)
    {
    }

    public function getPublicApiKey(): ?string
    {
        $response = $this->client->addApiKey(['search']);

        return $response['key'];
    }

    public function isAvailable(): bool
    {
        try {
            return $this->client->isAlive()['message'] === 'server is alive';
        } catch (Throwable) {
            return false;
        }
    }

    public function getVersion(): ?string
    {
        return null;
    }

    public function getHost(): ?string
    {
        return null;
    }

    public function updateIndexSettings(): void
    {
    }
}
