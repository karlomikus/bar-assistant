<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

final readonly class UpdateIngredientRequest
{
    /**
     * @param int[] $images
     * @param ComplexIngredientPart[] $complexIngredientParts
     * @param CreateIngredientPrice[] $prices
     */
    public function __construct(
        public int $ingredientId,
        public string $name,
        public int $userId,
        public float $strength = 0.0,
        public ?string $description = null,
        public ?string $origin = null,
        public ?string $color = null,
        public ?int $parentIngredientId = null,
        public array $images = [],
        public array $complexIngredientParts = [],
        public array $prices = [],
        public ?int $calculatorId = null,
        public ?float $sugarContent = null,
        public ?float $acidity = null,
        public ?string $distillery = null,
        public ?string $units = null,
    ) {
    }
}
