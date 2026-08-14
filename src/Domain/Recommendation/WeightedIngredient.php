<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Ingredient\IngredientId;

final readonly class WeightedIngredient
{
    public function __construct(
        public IngredientId $ingredientId,
        public Name $name,
        public float $weight,
    ) {
    }
}
