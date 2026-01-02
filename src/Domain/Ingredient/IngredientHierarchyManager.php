<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Ingredient\Exception\IngredientHierarchyException;

/**
 * Domain service for managing ingredient hierarchy operations.
 *
 * This service handles complex hierarchy operations that require
 * coordination between multiple ingredients, particularly when
 * updating materialized paths after hierarchy changes.
 */
final readonly class IngredientHierarchyManager
{
    public function __construct(
        private IngredientRepository $repository,
    ) {
    }

    /**
     * Change an ingredient's parent and update all descendants' materialized paths.
     *
     * This method ensures that when an ingredient is moved in the hierarchy,
     * all of its descendants have their materialized paths updated accordingly.
     *
     * @param Ingredient $ingredient The ingredient to move
     * @param Ingredient $newParent The new parent ingredient
     * @throws IngredientHierarchyException If the operation would create an invalid hierarchy
     */
    public function changeParent(Ingredient $ingredient, Ingredient $newParent): Ingredient
    {
        if ($ingredient->isTransient()) {
            throw new IngredientHierarchyException('Cannot change parent of transient ingredient');
        }

        if (!$ingredient->getBarId()->equals($newParent->getBarId())) {
            throw new IngredientHierarchyException('Ingredients must be from the same bar');
        }

        if ($ingredient->getId()->equals($newParent->getId())) {
            throw new IngredientHierarchyException('Ingredient cannot be its own parent');
        }

        if ($ingredient->isAncestorOf($newParent)) {
            throw new IngredientHierarchyException('Cannot move ingredient under its own descendant');
        }

        // Get all descendants before changing the parent
        $descendants = $this->repository->findDescendants($ingredient->getId());

        // Store the old materialized path structure
        $oldPath = $ingredient->getMaterializedPath();

        // Update path
        $ingredient->setAsVariantOf($newParent);

        // Calculate path difference for updating descendants
        $newPath = $ingredient->getMaterializedPath();

        // Update all descendants with their new paths
        foreach ($descendants as $descendant) {
            $this->rebuildDescendantPath($descendant, $oldPath, $newPath);
        }

        $this->repository->saveHierarchyChanges($ingredient, $descendants);

        return $ingredient;
    }

    /**
     * Make an ingredient a root (remove parent) and update all descendants' materialized paths.
     *
     * Example:
     * Before: Vodka / Premium Vodka / Grey Goose
     * Make Premium Vodka root: Premium Vodka / Grey Goose
     *
     * @param Ingredient $ingredient The ingredient to make root
     */
    public function makeRoot(Ingredient $ingredient): void
    {
        if ($ingredient->isTransient()) {
            throw new IngredientHierarchyException('Cannot make transient ingredient a root');
        }

        // Get all descendants before making root
        $descendants = $this->repository->findDescendants($ingredient->getId());

        // Store the old materialized path structure
        $oldPath = $ingredient->getMaterializedPath();

        // Make the ingredient a root
        $ingredient->setAsVariantOf(null);

        // New path is root
        $newPath = $ingredient->getMaterializedPath();

        // Update all descendants with their new paths
        foreach ($descendants as $descendant) {
            $this->rebuildDescendantPath($descendant, $oldPath, $newPath);
        }

        $this->repository->saveHierarchyChanges($ingredient, $descendants);
    }

    /**
     * Rebuild a descendant's path after its ancestor's path has changed.
     *
     * This method reconstructs the descendant's materialized path by:
     * 1. Finding where the descendant was relative to the old ancestor path
     * 2. Rebuilding the path starting from the new ancestor path
     * 3. Finding and setting the descendant's new parent
     *
     * @param Ingredient $descendant The descendant to update
     * @param MaterializedPath $oldAncestorPath The ancestor's old path
     * @param MaterializedPath $newAncestorPath The ancestor's new path
     */
    private function rebuildDescendantPath(
        Ingredient $descendant,
        MaterializedPath $oldAncestorPath,
        MaterializedPath $newAncestorPath
    ): void {
        $descendantPath = $descendant->getMaterializedPath();
        $oldAncestorIds = $oldAncestorPath->getAncestorIds();
        $descendantIds = $descendantPath->getAncestorIds();

        // Find the IDs that come after the old ancestor path
        $relativeIds = array_slice($descendantIds, count($oldAncestorIds));

        // Build new path: new ancestor path + relative path
        $newPath = $newAncestorPath;
        foreach ($relativeIds as $id) {
            $newPath = $newPath->append($id->id);
        }
    }
}
