<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;

interface IngredientRepository
{
    /**
     * List all ingredients in a bar
     *
     * @return Ingredient[]
     */
    public function list(BarId $barId): array;

    /**
     * Find an ingredient by its ID
     */
    public function findById(IngredientId $id): ?Ingredient;

    /**
     * Find multiple ingredients by their IDs within a specific bar
     *
     * @param IngredientId[] $ids
     * @return Ingredient[]
     */
    public function findMany(BarId $barId, array $ids): array;

    /**
     * Save an ingredient (insert or update)
     */
    public function save(Ingredient $ingredient): Ingredient;

    /**
     * Delete an ingredient by its ID
     */
    public function delete(IngredientId $id): void;

    /**
     * Find all direct children of an ingredient
     *
     * @return Ingredient[]
     */
    public function findChildren(IngredientId $parentId): array;
}
