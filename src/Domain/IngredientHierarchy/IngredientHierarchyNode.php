<?php

declare(strict_types=1);

namespace BarAssistant\Domain\IngredientHierarchy;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use BarAssistant\Domain\Ingredient\Exception\IngredientHierarchyException;

/**
 * Lightweight node representing an ingredient's position within a bar's hierarchy.
 *
 * Unlike the full Ingredient aggregate, this entity only carries the four
 * fields required for tree operations, making bulk moves efficient.
 */
final class IngredientHierarchyNode implements Identity
{
    private ?IngredientId $id = null;

    private function __construct(
        private readonly BarId $barId,
        private ?IngredientId $parentId,
        private MaterializedPath $materializedPath,
    ) {
    }

    public static function createRoot(
        BarId $barId,
    ): self {
        return new self(
            barId: $barId,
            parentId: null,
            materializedPath: MaterializedPath::root(),
        );
    }

    public static function createChild(
        BarId $barId,
        self $parent,
    ): self {
        if (!$parent->barId->equals($barId)) {
            throw new IngredientHierarchyException('Parent node must belong to the same bar');
        }

        if ($parent->isTransient()) {
            throw new IngredientHierarchyException('Cannot create child of transient node');
        }

        $node = new self(
            barId: $barId,
            parentId: $parent->getId(),
            materializedPath: $parent->materializedPath->append($parent->getId()),
        );

        return $node;
    }

    /**
     * @internal Used by the repository adapter when hydrating from persistence
     */
    public static function fromPersistence(
        BarId $barId,
        IngredientId $id,
        ?IngredientId $parentId,
        MaterializedPath $materializedPath,
    ): self {
        $node = new self(
            barId: $barId,
            parentId: $parentId,
            materializedPath: $materializedPath,
        );
        $node->id = $id;

        return $node;
    }

    public function getId(): ?IngredientId
    {
        return $this->id;
    }

    public function setId(IngredientId $id): self
    {
        if ($this->id !== null) {
            throw new IngredientHierarchyException('Cannot change the ID of an existing node');
        }

        $this->id = $id;

        return $this;
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    public function getParentId(): ?IngredientId
    {
        return $this->parentId;
    }

    public function getMaterializedPath(): MaterializedPath
    {
        return $this->materializedPath;
    }

    public function isRoot(): bool
    {
        return $this->materializedPath->isRoot();
    }

    public function isDescendantOf(self $other): bool
    {
        return $this->materializedPath->isDescendantOf($other->materializedPath);
    }

    public function isAncestorOf(self $other): bool
    {
        return $this->materializedPath->isAncestorOf($other->materializedPath);
    }

    /**
     * Change this node's parent and recompute its materialized path.
     *
     * @throws IngredientHierarchyException
     */
    public function changeParent(?self $parent): self
    {
        if ($this->isTransient()) {
            throw new IngredientHierarchyException('Cannot change parent of transient node');
        }

        if ($parent !== null) {
            if ($parent->isTransient()) {
                throw new IngredientHierarchyException('Cannot set transient node as parent');
            }

            if (!$this->barId->equals($parent->barId)) {
                throw new IngredientHierarchyException('Nodes must be from the same bar');
            }

            if ($this->id->equals($parent->id)) {
                throw new IngredientHierarchyException('Node cannot be its own parent');
            }

            if ($this->isAncestorOf($parent)) {
                throw new IngredientHierarchyException('Cannot move node under its own descendant');
            }
        }

        $this->parentId = $parent?->id;
        $this->materializedPath = $parent !== null && $parent->id !== null
            ? $parent->materializedPath->append($parent->id)
            : MaterializedPath::root();

        return $this;
    }

    /**
     * Rebase this node's materialized path by replacing the old base prefix
     * with a new base prefix.
     *
     * This is the core operation performed on all descendants after a parent move.
     */
    public function rebasePath(MaterializedPath $oldBase, MaterializedPath $newBase): self
    {
        $relativePath = $this->materializedPath->getRelativePath($oldBase);

        $rebased = $newBase;
        foreach ($relativePath->getAncestorIds() as $ancestorId) {
            $rebased = $rebased->append($ancestorId);
        }

        $this->materializedPath = $rebased;

        return $this;
    }

    /**
     * Elevate this node to a root by removing its parent.
     */
    public function makeRoot(): self
    {
        return $this->changeParent(null);
    }
}
