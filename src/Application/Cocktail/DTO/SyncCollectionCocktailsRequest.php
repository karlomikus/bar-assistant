<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class SyncCollectionCocktailsRequest
{
    /**
     * @param int[] $cocktailIds
     */
    public function __construct(
        public int $collectionId,
        public array $cocktailIds = [],
    ) {
    }
}
