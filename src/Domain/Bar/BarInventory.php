<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use BarAssistant\Domain\Ingredient\IngredientId;

final readonly class BarInventory
{
    /**
     * @param IngredientInventoryItem[] $ingredients
     */
    public function __construct(
        private array $ingredients,
    ) {
    }

    public function hasIngredient(IngredientId $ingredientId): bool
    {
        foreach ($this->ingredients as $existingInventoryItem) {
            if ($existingInventoryItem->ingredientId->equals($ingredientId)) {
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
                fn (IngredientId $existingInventoryItem) => !$existingInventoryItem->ingredientId->equals($ingredientId)
            );

            return new self(array_values($newIngredients));
        }

        return new self([...$this->ingredients, $ingredientId]);
    }

    /**
     * @return IngredientInventoryItem[]
     */
    public function getVariantIngredients(): array
    {
        return array_filter(
            $this->ingredients,
            fn (IngredientInventoryItem $item) => $item->ingredientStatus === IngredientInventoryStatus::Variant
        );
    }
}
