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

    public function hasIngredientInStock(IngredientId $ingredientId): bool
    {
        return array_any($this->ingredients, static fn($existingInventoryItem) => $existingInventoryItem->ingredientId->equals($ingredientId) && $existingInventoryItem->isInStock());
    }

    public function changeIngredientStock(IngredientId $ingredientId): self
    {
        if ($this->hasIngredientInStock($ingredientId)) {
            $newIngredients = array_filter(
                $this->ingredients,
                static fn (IngredientInventoryItem $existingInventoryItem) => !$existingInventoryItem->ingredientId->equals($ingredientId)
            );

            return new self(array_values($newIngredients));
        }

        return new self([...$this->ingredients, new IngredientInventoryItem($ingredientId, IngredientInventoryStatus::InStock)]);
    }

    /**
     * @return IngredientInventoryItem[]
     */
    public function getVariantIngredients(): array
    {
        return array_filter(
            $this->ingredients,
            static fn (IngredientInventoryItem $item) => $item->isInStockAsVariant()
        );
    }
}
