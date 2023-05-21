<?php

declare(strict_types=1);

namespace Kami\Cocktail\Search;

use Exception;
use Laravel\Scout\EngineManager;

class SearchActionsAdapter
{
    public function __construct(private EngineManager $engineManager)
    {
    }

    public function getActions(): ?SearchActionsContract
    {
        if (config('scout.driver') === 'meilisearch') {
            return $this->getMeilisearchActions();
        }

        if (config('scout.driver') === 'algolia') {
            return $this->getAlgoliaActions();
        }

        return $this->getNullActions();
    }

    private function getMeilisearchActions(): MeilisearchActions
    {
        $engine = $this->engineManager->engine();

        if ($engine instanceof \Meilisearch\Client) {
            return new MeilisearchActions($engine);
        }

        throw new Exception('Unknown search engine!');
    }

    private function getAlgoliaActions(): AlgoliaActions
    {
        $engine = $this->engineManager->engine();

        if ($engine instanceof \Algolia\AlgoliaSearch\SearchClient) {
            return new AlgoliaActions($engine);
        }

        throw new Exception('Unknown search engine!');
    }

    private function getNullActions(): NullActions
    {
        return new NullActions();
    }
}
