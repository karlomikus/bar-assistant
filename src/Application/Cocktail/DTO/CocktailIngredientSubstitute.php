<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class CocktailIngredientSubstitute
{
    public function __construct(
        public int $ingredientId,
        public ?float $amount,
        public ?string $units,
        public ?float $amountMax,
    ) {
    }
}
