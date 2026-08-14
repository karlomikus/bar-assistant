<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

interface CocktailRepository
{
    /**
     * Find an cocktail by its ID
     */
    public function findById(CocktailId $id): ?Cocktail;

    /**
     * Save an cocktail (insert or update)
     */
    public function save(Cocktail $cocktail): Cocktail;
}
