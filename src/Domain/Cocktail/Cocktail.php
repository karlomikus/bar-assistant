<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Identifier;
use BarAssistant\Domain\Identity;

final class Cocktail implements Identity
{
    private ?CocktailId $id = null;

    /**
     * @param CocktailIngredient[] $ingredients
     */
    public function __construct(
        private array $ingredients = [],
    )
    {
    }

    public function getId(): ?CocktailId
    {
        return $this->id;
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }
}
