<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;

interface IngredientRepository
{
    public function findById(IngredientId $id): ?Ingredient;

    /**
     * @param IngredientId[] $ids
     * @return Ingredient[]
     */
    public function findMany(BarId $barId, array $ids): array;

    public function save(Ingredient $ingredient): Ingredient;

    /**
     * Validate and save an ingredient with all its descendants after hierarchy change.
     *
     * This method should be saved atomic to ensure data integrity.
     *
     * @param Ingredient $ingredient The ingredient that was moved
     * @param Ingredient[] $descendants All affected descendants
     */
    public function saveHierarchyChanges(Ingredient $ingredient, array $descendants): void;

    // public function delete(IngredientId $id): void;

    /**
     * Find all root ingredients (ingredients without a parent)
     *
     * @return Ingredient[]
     */
    // public function findRoots(): array;

    /**
     * Find all direct children of an ingredient
     *
     * @return Ingredient[]
     */
    // public function findChildren(IngredientId $parentId): array;

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
    // public function findAncestors(IngredientId $descendantId): array;

    /**
     * Find the entire subtree rooted at the given ingredient (including the ingredient itself)
     *
     * @return Ingredient[]
     */
    // public function findSubtree(IngredientId $rootId): array;

    /**
     * Check if an ingredient exists
     */
    // public function exists(IngredientId $id): bool;
}
