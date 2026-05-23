<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class CocktailIngredient
{
    /**
     * @param CocktailIngredientSubstitute[] $substitutes
     */
    public function __construct(
        public int $ingredientId,
        public float $strength,
        public float $amount,
        public string $units,
        public int $sort,
        public bool $isOptional,
        public bool $isSpecified,
        public array $substitutes,
        public ?float $amountMax,
        public ?string $note,
    ) {
    }
}
