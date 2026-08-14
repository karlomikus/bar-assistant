<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use BarAssistant\Domain\Ingredient\IngredientId;

final readonly class IngredientInventoryItem
{
    public function __construct(
        public IngredientId $ingredientId,
        public IngredientInventoryStatus $ingredientStatus,
    ) {
    }

    public function isInStock(): bool
    {
        return $this->ingredientStatus === IngredientInventoryStatus::InStock;
    }

    public function isInStockAsVariant(): bool
    {
        return $this->ingredientStatus === IngredientInventoryStatus::Variant;
    }

    public function isInStockAsMakeable(): bool
    {
        return $this->ingredientStatus === IngredientInventoryStatus::Makeable;
    }
}
