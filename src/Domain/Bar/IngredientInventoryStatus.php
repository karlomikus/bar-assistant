<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

/**
 * Represents the status of an ingredient in a bar's inventory
 *
 * @internal
 */
enum IngredientInventoryStatus
{
    /**
     * The exact ingredient is in stock
     * Example: "London Dry Gin" is physically in the bar
     */
    case InStock;

    /**
     * A descendant/variant of this ingredient is in stock
     * Example: "Gin" is marked as variant because "London Dry Gin" is in stock
     */
    case Variant;

    /**
     * The ingredient can be made from other ingredients in stock
     * Example: "Lemon Juice" can be made from "Lemon" which is in stock
     */
    case Makeable;
}
