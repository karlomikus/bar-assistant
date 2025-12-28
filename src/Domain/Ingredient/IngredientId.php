<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

final readonly class IngredientId
{
    public function __construct(public int $id)
    {
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }
}
