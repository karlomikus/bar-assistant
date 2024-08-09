<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Draft2;

use JsonSerializable;

readonly class Schema implements JsonSerializable
{
    /**
     * @param array<Ingredient> $ingredients
     */
    public function __construct(
        public Cocktail $cocktail,
        public array $ingredients,
    ) {
    }

    public function toArray(): array
    {
        return [
            'recipe' => $this->cocktail->toArray(),
            'ingredients' => $this->ingredients,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
