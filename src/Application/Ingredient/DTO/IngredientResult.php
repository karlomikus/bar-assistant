<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

use DateTimeImmutable;

final readonly class IngredientResult
{
    /**
     * @param int[] $images
     * @param int[] $complexIngredientParts
     * @param IngredientPriceResult[] $prices
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $userId,
        public DateTimeImmutable $createdAt,
        public string $materializedPath,
        public ?string $description = null,
        public float $strength = 0.0,
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
        public ?int $updatedBy = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {
    }
}
