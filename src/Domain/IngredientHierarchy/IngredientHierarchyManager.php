<?php

declare(strict_types=1);

namespace BarAssistant\Domain\IngredientHierarchy;

use BarAssistant\Domain\Ingredient\Exception\IngredientHierarchyException;

/**
 * Domain service for managing ingredient hierarchy operations.
 *
 * This service coordinates tree moves that span multiple nodes,
 * ensuring all descendants have their materialized paths updated
 * consistently after a parent change.
 */
final readonly class IngredientHierarchyManager
{
    public function __construct(
        private IngredientHierarchyRepository $repository,
    ) {
    }

    /**
     * Change a node's parent and update all descendants' materialized paths.
     *
     * @param IngredientHierarchyNode $node The node to move
     * @param IngredientHierarchyNode $newParent The new parent node
     */
    public function changeParent(IngredientHierarchyNode $node, IngredientHierarchyNode $newParent): IngredientHierarchyNode
    {
        $this->guardMove($node, $newParent, 'change parent');

        $nodeId = $node->getId();
        \assert($nodeId !== null);

        $descendants = $this->repository->findDescendants($nodeId, $node->getBarId());

        $oldPath = $node->getMaterializedPath();

        $node->changeParent($newParent);

        $newPath = $node->getMaterializedPath();

        $oldBase = $oldPath->append($nodeId);
        $newBase = $newPath->append($nodeId);

        foreach ($descendants as $descendant) {
            $descendant->rebasePath(oldBase: $oldBase, newBase: $newBase);
        }

        $this->repository->saveHierarchyMove($node, $descendants);

        return $node;
    }

    /**
     * Make a node a root (remove parent) and update all descendants' paths.
     *
     * @param IngredientHierarchyNode $node The node to make root
     */
    public function makeRoot(IngredientHierarchyNode $node): IngredientHierarchyNode
    {
        if ($node->isTransient()) {
            throw new IngredientHierarchyException('Cannot make transient node a root');
        }

        $nodeId = $node->getId();

        $descendants = $this->repository->findDescendants($nodeId, $node->getBarId());

        $oldPath = $node->getMaterializedPath();

        $node->makeRoot();

        $newPath = $node->getMaterializedPath();

        $oldBase = $oldPath->append($nodeId);
        $newBase = $newPath->append($nodeId);

        foreach ($descendants as $descendant) {
            $descendant->rebasePath(oldBase: $oldBase, newBase: $newBase);
        }

        $this->repository->saveHierarchyMove($node, $descendants);

        return $node;
    }

    private function guardMove(IngredientHierarchyNode $node, IngredientHierarchyNode $newParent, string $context): void
    {
        if ($node->isTransient()) {
            throw new IngredientHierarchyException("Cannot {$context} of transient node");
        }

        $nodeId = $node->getId();

        if ($newParent->isTransient()) {
            throw new IngredientHierarchyException("Cannot set transient node as parent");
        }

        if (!$node->getBarId()->equals($newParent->getBarId())) {
            throw new IngredientHierarchyException('Nodes must be from the same bar');
        }

        if ($nodeId->equals($newParent->getId())) {
            throw new IngredientHierarchyException('Node cannot be its own parent');
        }

        if ($node->isAncestorOf($newParent)) {
            throw new IngredientHierarchyException('Cannot move node under its own descendant');
        }
    }
}
