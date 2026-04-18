<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Common\Name;

final readonly class IngredientMatch implements Identity
{
    private function __construct(
        private IngredientId $ingredientId,
        private Name $name,
    ) {
    }

    public static function create(
        IngredientId $ingredientId,
        Name $name,
    ): self {
        return new self(
            ingredientId: $ingredientId,
            name: $name,
        );
    }

    public function getId(): IngredientId
    {
        return $this->ingredientId;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function isTransient(): bool
    {
        return false;
    }
}
