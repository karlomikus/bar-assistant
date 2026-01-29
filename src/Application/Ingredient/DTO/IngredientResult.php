<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

use BarAssistant\Domain\Ingredient\Ingredient;
use DateTimeImmutable;

final readonly class IngredientResult
{
    public function __construct(
        public int $id,
        public int $createdBy,
        public DateTimeImmutable $createdAt,
        public ?int $updatedBy = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    /**
     * @param Ingredient[] $ancestors
     */
    public static function fromIngredient(Ingredient $ingredient): IngredientResult
    {
        return new IngredientResult(
            id: $ingredient->getId() ? $ingredient->getId()->value : 0,
            createdBy: $ingredient->getAuthors()->getCreatedBy()->value,
            createdAt: $ingredient->getRecordTimestamps()->getCreatedAt(),
            updatedBy: $ingredient->getAuthors()->getUpdatedBy()?->value,
            updatedAt: $ingredient->getRecordTimestamps()->getUpdatedAt(),
        );
    }
}
