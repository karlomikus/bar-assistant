<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Common\Name;

final readonly class CocktailMatch implements Identity
{
    private function __construct(
        private CocktailId $cocktailId,
        private Name $name,
    ) {
    }

    public static function create(
        CocktailId $cocktailId,
        Name $name,
    ): self {
        return new self(
            cocktailId: $cocktailId,
            name: $name,
        );
    }

    public function getId(): CocktailId
    {
        return $this->cocktailId;
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
