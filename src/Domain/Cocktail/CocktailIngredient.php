<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Support\AmountWithUnits;

final class CocktailIngredient extends Ingredient
{
    /**
     * @param CocktailIngredientSubstitute[] $substitutes 
     */
    public function __construct(
        public IngredientId $ingredientId,
        public AmountWithUnits $amountWithUnits,
        public float $abv,
        public bool $isOptional,
        public bool $isSpecific,
        public ?string $note = null,
        public array $substitutes = [],
    )
    {
        parent::__construct(
            ingredientId: $ingredientId,
            amountWithUnits: $amountWithUnits,
            abv: $abv,
        );
    }
}
