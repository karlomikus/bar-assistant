<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

interface CocktailMethodRepository
{
    /**
     * Find a cocktail method by its identifier
     */
    public function findById(MethodId $id): ?CocktailMethod;

    /**
     * Save a cocktail method (insert or update)
     */
    public function save(CocktailMethod $cocktailMethod): CocktailMethod;

    /**
     * Delete a cocktail method by its identifier
     */
    public function delete(MethodId $id): void;
}
