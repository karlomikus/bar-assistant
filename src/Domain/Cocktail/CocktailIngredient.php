<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Common\ABV;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Ingredient\IngredientId;

final readonly class CocktailIngredient
{
    /**
     * @param CocktailIngredientSubstitute[] $substitutes
     */
    private function __construct(
        public IngredientId $ingredientId,
        public AmountWithUnits $amountWithUnits,
        public ABV $abv,
        public bool $isOptional,
        public bool $isSpecific,
        public int $sortIndex = 0,
        public ?string $note = null,
        public array $substitutes = [],
    ) {
    }

    /**
     * @param CocktailIngredientSubstitute[] $substitutes
     */
    public static function create(
        IngredientId $ingredientId,
        AmountWithUnits $amountWithUnits,
        ABV $abv,
        bool $isOptional = false,
        bool $isSpecific = false,
        int $sortIndex = 0,
        ?string $note = null,
        array $substitutes = [],
    ): self {
        return new self(
            ingredientId: $ingredientId,
            amountWithUnits: $amountWithUnits,
            abv: $abv,
            isOptional: $isOptional,
            isSpecific: $isSpecific,
            sortIndex: $sortIndex,
            note: $note,
            substitutes: $substitutes,
        );
    }
}
