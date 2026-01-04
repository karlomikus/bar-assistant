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
}
