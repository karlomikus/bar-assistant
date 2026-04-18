<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI\DTO;

final readonly class CompleteIngredientRequest
{
    public function __construct(
        public string $ingredientName,
    ) {
    }
}
