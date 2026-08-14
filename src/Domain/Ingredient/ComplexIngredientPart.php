<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Common\AmountWithUnits;

/**
 * A constituent part of a complex ingredient, with amount and optional note.
 */
final readonly class ComplexIngredientPart
{
    private function __construct(
        private IngredientId $ingredientId,
        private AmountWithUnits $amountWithUnits,
        private ?string $note = null,
    ) {
    }

    public static function create(
        IngredientId $ingredientId,
        AmountWithUnits $amountWithUnits,
        ?string $note = null,
    ): self {
        return new self($ingredientId, $amountWithUnits, $note);
    }

    public function getIngredientId(): IngredientId
    {
        return $this->ingredientId;
    }

    public function getAmountWithUnits(): AmountWithUnits
    {
        return $this->amountWithUnits;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ingredient_id' => $this->ingredientId->value,
            ...$this->amountWithUnits->toArray(),
            'note' => $this->note,
        ];
    }
}
