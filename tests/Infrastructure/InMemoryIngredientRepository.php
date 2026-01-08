<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\IngredientRepository;

/**
 * In-memory implementation of IngredientRepository for testing purposes
 */
final class InMemoryIngredientRepository implements IngredientRepository
{
    private int $nextId = 1;

    /**
     * @param array<int, Ingredient> $ingredients
     */
    public function __construct(private array $ingredients = []) {}

    /**
     * List all ingredients in a bar
     *
     * @return Ingredient[]
     */
    public function list(BarId $barId): array
    {
        return array_values(array_filter(
            $this->ingredients,
            fn(Ingredient $ingredient) => $ingredient->getBarId()->equals($barId)
        ));
    }

    /**
     * Find an ingredient by its ID
     */
    public function findById(IngredientId $id): ?Ingredient
    {
        return $this->ingredients[$id->value] ?? null;
    }

    /**
     * Find multiple ingredients by their IDs within a specific bar
     *
     * @param IngredientId[] $ids
     * @return Ingredient[]
     */
    public function findMany(BarId $barId, array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            $ingredient = $this->findById($id);
            if ($ingredient !== null && $ingredient->getBarId()->equals($barId)) {
                $result[] = $ingredient;
            }
        }

        return $result;
    }

    /**
     * Save an ingredient (insert or update)
     */
    public function save(Ingredient $ingredient): Ingredient
    {
        if ($ingredient->isTransient()) {
            $ingredient->setId(new IngredientId($this->nextId++));
        }

        $this->ingredients[$ingredient->getId()->value] = $ingredient;

        return $ingredient;
    }

    /**
     * Validate and save an ingredient with all its descendants after hierarchy change.
     *
     * This method should be atomic to ensure data integrity.
     *
     * @param Ingredient $ingredient The ingredient that was moved
     * @param Ingredient[] $descendants All affected descendants
     */
    public function saveHierarchyChanges(Ingredient $ingredient, array $descendants): void
    {
        // Save the moved ingredient
        $this->save($ingredient);

        // Save all affected descendants
        foreach ($descendants as $descendant) {
            $this->save($descendant);
        }
    }

    /**
     * Delete an ingredient by its ID
     */
    public function delete(IngredientId $id): void
    {
        unset($this->ingredients[$id->value]);
    }

    /**
     * Find all direct children of an ingredient
     *
     * @return Ingredient[]
     */
    public function findChildren(IngredientId $parentId): array
    {
        return array_values(array_filter(
            $this->ingredients,
            function (Ingredient $ingredient) use ($parentId) {
                $parent = $ingredient->getParentIngredientId();
                return $parent !== null && $parent->equals($parentId);
            }
        ));
    }

    /**
     * Find all descendants (children, grandchildren, etc.) of an ingredient
     *
     * @return Ingredient[]
     */
    public function findDescendants(Ingredient $ingredient): array
    {
        $path = $ingredient->getMaterializedPath();
        $ingredientId = $ingredient->getId();

        if ($ingredientId === null) {
            return [];
        }

        // Descendants are those whose materialized path starts with this ingredient's path + its ID
        $searchPath = $path->append($ingredientId);

        return array_values(array_filter(
            $this->ingredients,
            function (Ingredient $candidate) use ($searchPath, $ingredient) {
                // Skip the ingredient itself
                if ($ingredient->getId() !== null && 
                    $candidate->getId() !== null && 
                    $candidate->getId()->equals($ingredient->getId())) {
                    return false;
                }

                return $candidate->getMaterializedPath()->isDescendantOf($searchPath);
            }
        ));
    }

    /**
     * Find all ancestors (parent, grandparent, etc.) of an ingredient
     * Uses materialized path for efficient querying
     *
     * @return Ingredient[]
     */
    public function findAncestors(IngredientId $descendantId): array
    {
        $descendant = $this->findById($descendantId);
        if ($descendant === null) {
            return [];
        }

        $ancestorIds = $descendant->getMaterializedPath()->getAncestorIds();
        if (empty($ancestorIds)) {
            return [];
        }

        $ancestors = [];
        foreach ($ancestorIds as $ancestorId) {
            $ancestor = $this->findById($ancestorId);
            if ($ancestor !== null) {
                $ancestors[] = $ancestor;
            }
        }

        return $ancestors;
    }
}
