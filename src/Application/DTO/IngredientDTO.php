<?php

declare(strict_types=1);

namespace BarAssistant\Application\DTO;

final readonly class IngredientDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description = null,
    ) {
    }

    public static function fromDomain(\BarAssistant\Domain\Ingredient\Ingredient $ingredient): self
    {
        return new self(
            id: $ingredient->getId() ? $ingredient->getId()->id : 0,
            name: $ingredient->getName(),
            description: $ingredient->getDescription(),
        );
    }
}
