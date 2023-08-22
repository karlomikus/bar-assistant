<?php

declare(strict_types=1);

namespace Kami\Cocktail\Search;

use Throwable;
use Laravel\Scout\Engines\MeiliSearchEngine;

class MeilisearchActions implements SearchActionsContract
{
    public function __construct(private readonly MeiliSearchEngine $client)
    {
    }

    public function getBarSearchApiKey(int $barId): ?string
    {
        $apiKey = $this->client->getKey(config('scout.meilisearch.api_key'));

        $rules = (object) [
            'cocktails' => (object) [
                'filter' => 'bar_id = ' . $barId,
            ],
            'ingredients' => (object) [
                'filter' => 'bar_id = ' . $barId,
            ]
        ];

        $tenantToken = $this->client->generateTenantToken($apiKey->getUid(), $rules, ['apiKey' => $apiKey->getKey()]);

        return $tenantToken;
    }

    public function deleteBarSearchApiKey(string $key): void
    {
        $this->client->deleteKey($key);
    }

    public function isAvailable(): bool
    {
        try {
            return $this->client->isHealthy();
        } catch (Throwable) {
            return false;
        }
    }

    public function getVersion(): ?string
    {
        try {
            return $this->client->version()['pkgVersion'];
        } catch (Throwable) {
            return null;
        }
    }

    public function getHost(): ?string
    {
        return config('scout.meilisearch.host');
    }

    public function updateIndexSettings(): void
    {
        $this->client->index('cocktails')->updateSettings([
            'filterableAttributes' => ['tags', 'bar_id'],
            'sortableAttributes' => ['name', 'date'],
            'searchableAttributes' => [
                'name',
                'tags',
                'description',
                'date',
                'short_ingredients',
            ]
        ]);

        $this->client->index('ingredients')->updateSettings([
            'filterableAttributes' => ['category', 'origin', 'bar_id'],
            'sortableAttributes' => ['name', 'category'],
            'searchableAttributes' => [
                'name',
                'description',
                'category',
                'origin',
            ]
        ]);
    }
}
