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
     * Validate and save an ingredient with all its descendants after hierarchy change.
     *
     * This method should be atomic to ensure data integrity.
     *
     * @param Ingredient $ingredient The ingredient that was moved
     * @param Ingredient[] $descendants All affected descendants
     */
    public function saveHierarchyChanges(Ingredient $ingredient, array $descendants): void;

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

    /**
     * Find all descendants (children, grandchildren, etc.) of an ingredient
     *
     * @return Ingredient[]
     */
    public function findDescendants(Ingredient $ingredient): array;

    /**
     * Find all ancestors (parent, grandparent, etc.) of an ingredient
     * Uses materialized path for efficient querying
     *
     * @return Ingredient[]
     */
    public function findAncestors(IngredientId $descendantId): array;

    /**
     * Checks if any of the ingredients belong to the provided bar
     *
     * @param IngredientId[] $ingredientIds
     *
     * @return bool Returns false if any of the ingredients do not belong to the bar
     */
    public function checkBarOwnership(BarId $barId, array $ingredientIds): bool;
}
