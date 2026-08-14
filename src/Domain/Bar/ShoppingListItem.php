<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use DomainException;
use BarAssistant\Domain\Ingredient\IngredientId;

final readonly class ShoppingListItem
{
    private function __construct(
        public IngredientId $ingredientId,
        public int $quantity,
    ) {
        if ($quantity < 1) {
            throw new DomainException('Quantity must be at least 1.');
        }
    }

    public static function create(IngredientId $ingredientId, int $quantity): self
    {
        return new self($ingredientId, $quantity);
    }
}
