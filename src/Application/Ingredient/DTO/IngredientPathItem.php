<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

/**
 * Represents a single ingredient in the ancestor path
 */
final readonly class IngredientPathItem
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
