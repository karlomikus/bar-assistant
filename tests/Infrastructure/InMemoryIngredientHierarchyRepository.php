<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyNode;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyRepository;

/**
 * In-memory implementation of IngredientHierarchyRepository for testing purposes
 */
final class InMemoryIngredientHierarchyRepository implements IngredientHierarchyRepository
{
    /**
     * @param array<int, IngredientHierarchyNode> $nodes
     */
    public function __construct(private array $nodes = [])
    {
    }

    public function findById(IngredientId $id): ?IngredientHierarchyNode
    {
        return $this->nodes[$id->value] ?? null;
    }

    /**
     * Find all descendants (children, grandchildren, etc.) of a node.
     *
     * @return IngredientHierarchyNode[]
     */
    public function findDescendants(IngredientId $id, BarId $barId): array
    {
        $root = $this->findById($id);
        if ($root === null) {
            return [];
        }

        $searchPath = $root->getMaterializedPath()->append($id);
        $prefix = $searchPath->toString();

        return array_values(array_filter(
            $this->nodes,
            static function (IngredientHierarchyNode $candidate) use ($prefix, $id, $barId): bool {
                if ($candidate->getId()->equals($id)) {
                    return false;
                }

                if (!$candidate->getBarId()->equals($barId)) {
                    return false;
                }

                $candidatePath = $candidate->getMaterializedPath()->toString();

                return $candidatePath !== '' && str_starts_with($candidatePath, $prefix);
            }
        ));
    }

    /**
     * Find all ancestors (parent, grandparent, etc.) of a node.
     *
     * @return IngredientHierarchyNode[]
     */
    public function findAncestors(IngredientId $id, BarId $barId): array
    {
        $node = $this->findById($id);
        if ($node === null) {
            return [];
        }

        $ancestorIds = $node->getMaterializedPath()->getAncestorIds();
        if (count($ancestorIds) === 0) {
            return [];
        }

        $ancestors = [];
        foreach ($ancestorIds as $ancestorId) {
            $ancestor = $this->findById($ancestorId);
            if ($ancestor !== null && $ancestor->getBarId()->equals($barId)) {
                $ancestors[] = $ancestor;
            }
        }

        return $ancestors;
    }

    public function save(IngredientHierarchyNode $node): void
    {
        if ($node->isTransient()) {
            throw new \RuntimeException('Cannot save transient node - assign an ID first');
        }

        $this->nodes[$node->getId()->value] = $node;
    }

    /**
     * @inheritDoc
     */
    public function saveHierarchyMove(IngredientHierarchyNode $movedNode, array $descendants): void
    {
        $this->save($movedNode);

        foreach ($descendants as $descendant) {
            $this->save($descendant);
        }
    }
}
