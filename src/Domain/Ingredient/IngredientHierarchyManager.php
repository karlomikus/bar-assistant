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
        $descendants = $this->repository->findDescendants($ingredient);

        // Store the old materialized path structure
        $oldParentIngredientId = $ingredient->getParentIngredientId();
        $oldPath = $ingredient->getMaterializedPath();

        // Update path
        $ingredient->setAsVariantOf($newParent);

        // Calculate path difference for updating descendants
        $newPath = $ingredient->getMaterializedPath();

        // Descendats are a flat array and we need to update their paths and parent ids
        foreach ($descendants as $descendant) {
            if ($descendant->getId()->equals($ingredient->getId())) {
                continue; // Skip the moved ingredient itself
            }

            // Direct child of the moved ingredient
            if ($descendant->getParentIngredientId()->equals($oldParentIngredientId)) {
                $descendant->setParentIngredientId($newParent->getId());
            }

            // $descendant->materialized_path = str_replace($oldPath, $newPath ?? '', $descendant->materialized_path);

            // Calculate new materialized path
            $relativePath = substr(
                $descendant->getMaterializedPath()->toString(),
                strlen($oldPath->toString())
            );

            $newMaterializedPathString = $newPath->toString() . $relativePath;
            $descendant->setMaterializedPath(MaterializedPath::fromString($newMaterializedPathString));

            // // Update parent ingredient id
            // $parentId = $descendant->getMaterializedPath()->getLastId();
            // $descendant->setParentIngredientId($parentId);
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
        $descendants = $this->repository->findDescendants($ingredient);

        // Store the old materialized path structure
        $oldPath = $ingredient->getMaterializedPath();

        // Make the ingredient a root
        $ingredient->setAsVariantOf(null);

        // New path is root
        $newPath = $ingredient->getMaterializedPath();

        $this->repository->saveHierarchyChanges($ingredient, $descendants);
    }
}

