<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Cocktail\CollectionId;
use BarAssistant\Domain\Cocktail\CocktailCollection;
use BarAssistant\Domain\Cocktail\CocktailCollectionRepository;

final class InMemoryCocktailCollectionRepository implements CocktailCollectionRepository
{
    /**
     * @param array<int, CocktailCollection> $collections
     */
    public function __construct(private array $collections = [])
    {
    }

    public function findById(CollectionId $id): ?CocktailCollection
    {
        return $this->collections[$id->value] ?? null;
    }

    public function save(CocktailCollection $collection): CocktailCollection
    {
        if ($collection->isTransient()) {
            $nextId = empty($this->collections) ? 1 : max(array_keys($this->collections)) + 1;
            $collection->setId(new CollectionId($nextId));
        }

        $collectionId = $collection->getId();
        if ($collectionId === null) {
            throw new \DomainException('Collection ID must be set');
        }

        $this->collections[$collectionId->value] = $collection;

        return $collection;
    }

    public function delete(CollectionId $id): void
    {
        unset($this->collections[$id->value]);
    }
}
