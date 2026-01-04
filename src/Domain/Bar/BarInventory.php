<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use BarAssistant\Domain\Ingredient\IngredientId;

final readonly class BarInventory
{
    /**
     * @param IngredientId[] $ingredients
     */
    public function __construct(
        private array $ingredients,
    ) {
    }

    public function hasIngredient(IngredientId $ingredientId): bool
    {
        foreach ($this->ingredients as $existingIngredientId) {
            if ($existingIngredientId->equals($ingredientId)) {
                return true;
            }
        }

        return false;
    }

    public function changeIngredientAvailability(IngredientId $ingredientId): self
    {
        if ($this->hasIngredient($ingredientId)) {
            $newIngredients = array_filter(
                $this->ingredients,
                fn (IngredientId $existingIngredientId) => !$existingIngredientId->equals($ingredientId)
            );

            return new self(array_values($newIngredients));
        }

        return new self([...$this->ingredients, $ingredientId]);
    }
}
