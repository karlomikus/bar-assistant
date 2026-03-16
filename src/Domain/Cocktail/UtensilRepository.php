<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

interface UtensilRepository
{
    /**
     * Find a utensil by its ID
     */
    public function findById(UtensilId $id): ?Utensil;

    /**
     * Save a utensil (insert or update)
     */
    public function save(Utensil $utensil): Utensil;

    /**
     * Delete a utensil by its ID
     */
    public function delete(UtensilId $id): void;
}
