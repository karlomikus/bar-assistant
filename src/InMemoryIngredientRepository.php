<?php

declare(strict_types=1);

namespace BarAssistant;

use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientRepository;

final class InMemoryIngredientRepository implements IngredientRepository
{
    private array $ingredients = [];

    public function find(IngredientId $id): ?Ingredient
    {
        return $this->ingredients[$id->id] ?? null;
    }

    public function findMany(array $ids): array
    {
        $foundIngredients = [];
        foreach ($ids as $id) {
            if (isset($this->ingredients[$id])) {
                $foundIngredients[] = $this->ingredients[$id];
            }
        }
        return $foundIngredients;
    }

    public function save(Ingredient $ingredient): Ingredient
    {
        $ingredient = $ingredient->withId(new IngredientId(rand()));

        $this->ingredients[$ingredient->getId()->id] = $ingredient;

        return $ingredient;
    }
}
