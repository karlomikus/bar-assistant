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
    public function __construct(private array $ingredients = [])
    {
    }

    /**
     * List all ingredients in a bar
     *
     * @return Ingredient[]
     */
    public function list(BarId $barId): array
    {
        return array_values(array_filter(
            $this->ingredients,
            fn (Ingredient $ingredient) => $ingredient->getBarId()->equals($barId)
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
}
