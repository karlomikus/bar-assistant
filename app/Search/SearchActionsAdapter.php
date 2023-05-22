<?php

declare(strict_types=1);

namespace Kami\Cocktail\Search;

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
        return new MeilisearchActions($this->engineManager->engine());
    }

    private function getAlgoliaActions(): AlgoliaActions
    {
        return new AlgoliaActions($this->engineManager->engine());
    }

    private function getNullActions(): NullActions
    {
        return new NullActions();
    }
}
