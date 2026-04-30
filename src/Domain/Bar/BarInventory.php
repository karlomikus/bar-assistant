<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use BarAssistant\Domain\Ingredient\IngredientId;

final class BarInventory
{
    /**
     * @param IngredientInventoryItem[] $ingredients
     */
    private function __construct(
        private BarId $barId,
        private array $ingredients = [],
    ) {
    }

    /**
     * @param IngredientInventoryItem[] $ingredients
     */
    public static function create(BarId $barId, array $ingredients = []): self
    {
        return new self(
            barId: $barId,
            ingredients: $ingredients,
        );
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    /**
     * @return IngredientInventoryItem[]
     */
    public function getIngredients(): array
    {
        return $this->ingredients;
    }

    public function putIngredient(IngredientId $ingredientId, IngredientInventoryStatus $status): self
    {
        $this->removeIngredient($ingredientId);

        $this->ingredients[] = new IngredientInventoryItem($ingredientId, $status);

        return $this;
    }

    public function removeIngredient(IngredientId $ingredientId): self
    {
        $this->ingredients = array_values(array_filter(
            $this->ingredients,
            static fn (IngredientInventoryItem $inventoryIngredient): bool => !$inventoryIngredient->ingredientId->equals($ingredientId)
        ));

        return $this;
    }

    /**
     * @return IngredientInventoryItem[]
     */
    public function getVariantIngredients(): array
    {
        return array_values(array_filter(
            $this->ingredients,
            static fn (IngredientInventoryItem $item): bool => $item->isInStockAsVariant()
        ));
    }

    /**
     * @return IngredientInventoryItem[]
     */
    public function getInStockIngredients(): array
    {
        return array_values(array_filter(
            $this->ingredients,
            static fn (IngredientInventoryItem $item): bool => $item->isInStock()
        ));
    }
}
