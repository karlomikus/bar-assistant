<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Ingredient\IngredientId;

final readonly class CocktailIngredientSubstitute
{
    private function __construct(
        public IngredientId $ingredientId,
        public ?AmountWithUnits $amountWithUnits = null,
    ) {
    }

    public static function create(
        IngredientId $ingredientId,
        ?AmountWithUnits $amountWithUnits = null,
    ): self {
        return new self(
            ingredientId: $ingredientId,
            amountWithUnits: $amountWithUnits,
        );
    }
}
