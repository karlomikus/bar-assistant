<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

interface CocktailCollectionRepository
{
    public function findById(CollectionId $id): ?CocktailCollection;

    public function save(CocktailCollection $collection): CocktailCollection;

    public function delete(CollectionId $id): void;
}
