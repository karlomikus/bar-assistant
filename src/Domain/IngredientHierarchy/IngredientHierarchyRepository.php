<?php

declare(strict_types=1);

namespace BarAssistant\Domain\IngredientHierarchy;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\IngredientId;

interface IngredientHierarchyRepository
{
    /**
     * Find a single node by ingredient ID.
     */
    public function findById(IngredientId $id): ?IngredientHierarchyNode;

    /**
     * Find all descendants (children, grandchildren, etc.) of a node.
     *
     * @return IngredientHierarchyNode[]
     */
    public function findDescendants(IngredientId $id, BarId $barId): array;

    /**
     * Find all ancestors (parent, grandparent, etc.) of a node.
     *
     * @return IngredientHierarchyNode[]
     */
    public function findAncestors(IngredientId $id, BarId $barId): array;

    /**
     * Persist a node's hierarchy state (parent ID + materialized path).
     */
    public function save(IngredientHierarchyNode $node): void;

    /**
     * Atomically persist a moved node and all updated descendants.
     *
     * Implementations may batch this into a single SQL statement for efficiency.
     *
     * @param IngredientHierarchyNode $movedNode The node whose parent changed
     * @param IngredientHierarchyNode[] $descendants All descendants with rebased paths
     */
    public function saveHierarchyMove(IngredientHierarchyNode $movedNode, array $descendants): void;
}
