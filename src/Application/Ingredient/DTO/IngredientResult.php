<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

use BarAssistant\Domain\Ingredient\Ingredient;
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
        public int $barId,
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

    public static function fromIngredient(Ingredient $ingredient): IngredientResult
    {
        $images = [];
        foreach ($ingredient->getImages() as $imageId) {
            $images[] = $imageId->id;
        }

        $complexIngredientParts = [];
        foreach ($ingredient->getIngredientParts() as $ingredientId) {
            $complexIngredientParts[] = $ingredientId->id;
        }

        $prices = [];
        foreach ($ingredient->getPrices() as $price) {
            $prices[] = IngredientPriceResult::fromIngredientPrice($price);
        }

        return new IngredientResult(
            id: $ingredient->getId() ? $ingredient->getId()->id : 0,
            barId: $ingredient->getBarId()->id,
            name: $ingredient->getName(),
            userId: $ingredient->getAuthors()->getCreatedBy()->id,
            createdAt: $ingredient->getRecordTimestamps()->getCreatedAt(),
            materializedPath: $ingredient->getMaterializedPath()->toString(),
            description: $ingredient->getDescription(),
            strength: $ingredient->getStrength() ?? 0.0,
            origin: $ingredient->getOrigin(),
            color: $ingredient->getColor()?->toHexString(),
            parentIngredientId: $ingredient->getParentIngredientId()?->id,
            calculatorId: $ingredient->getCalculatorId()?->id,
            sugarContent: $ingredient->getSugarContent(),
            acidity: $ingredient->getAcidity(),
            distillery: $ingredient->getDistillery(),
            units: $ingredient->getUnits()?->value,
            updatedBy: $ingredient->getAuthors()->getUpdatedBy()?->id,
            updatedAt: $ingredient->getRecordTimestamps()->getUpdatedAt(),
            images: $images,
            complexIngredientParts: $complexIngredientParts,
            prices: $prices,
        );
    }
}
