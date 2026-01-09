<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Support\AmountWithUnits;

abstract class Ingredient
{
    public function __construct(
        public IngredientId $ingredientId,
        public AmountWithUnits $amountWithUnits,
        public float $abv,
    )
    {
    }
}
