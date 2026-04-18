<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Bar\BarId;

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

    /**
     * @return CocktailMethod[]
     */
    public function findAllInBar(BarId $barId): array;
}
