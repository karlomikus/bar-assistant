<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;

interface IngredientRepository
{
    public function find(IngredientId $id): ?Ingredient;

    public function findMany(BarId $barId, array $ids): array;

    public function save(Ingredient $ingredient): Ingredient;

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
    public function findDescendants(IngredientId $ancestorId): array;

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
