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

        return $this->getNullActions();
    }

    private function getMeilisearchActions()
    {
        return new MeilisearchActions($this->engineManager->engine());
    }

    private function getNullActions()
    {
        return new NullActions();
    }
}
