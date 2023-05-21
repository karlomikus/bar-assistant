<?php

declare(strict_types=1);

namespace Kami\Cocktail\Search;

use Throwable;
use Meilisearch\Client;

class MeilisearchActions implements SearchActionsContract
{
    public function __construct(private readonly Client $meilisearchClient)
    {
    }

    public function getPublicApiKey(): ?string
    {
        $key = $this->meilisearchClient->createKey([
            'actions' => ['search'],
            'indexes' => ['cocktails', 'ingredients'],
            'expiresAt' => null,
            'name' => 'Bar Assistant',
            'description' => 'Client key generated by Bar Assistant Server'
        ]);

        return $key->getKey();
    }

    public function isAvailable(): bool
    {
        try {
            return $this->meilisearchClient->isHealthy();
        } catch (Throwable) {
            return false;
        }
    }

    public function getVersion(): ?string
    {
        try {
            return $this->meilisearchClient->version()['pkgVersion'];
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
        $this->meilisearchClient->index('cocktails')->updateSettings([
            'filterableAttributes' => ['id', 'tags', 'user_id', 'glass', 'average_rating', 'main_ingredient_name', 'method', 'calculated_abv', 'has_public_link'],
            'sortableAttributes' => ['name', 'date', 'average_rating'],
            'searchableAttributes' => [
                'name',
                'tags',
                'description',
                'date',
            ]
        ]);

        $this->meilisearchClient->index('cocktails')->updatePagination(['maxTotalHits' => 2000]);

        $this->meilisearchClient->index('ingredients')->updateSettings([
            'filterableAttributes' => ['category', 'strength_abv', 'origin', 'color', 'id'],
            'sortableAttributes' => ['name', 'strength_abv'],
            'searchableAttributes' => [
                'name',
                'description',
                'category',
                'origin',
            ]
        ]);

        $this->meilisearchClient->index('ingredients')->updatePagination(['maxTotalHits' => 2000]);
    }
}
