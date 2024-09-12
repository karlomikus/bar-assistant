<?php

declare(strict_types=1);

namespace Kami\Cocktail\DTO\Ingredient;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Kami\Cocktail\Models\PriceCategory;

readonly class Price
{
    public function __construct(
        public int $priceCategoryId,
        public int $price,
        public float $amount,
        public string $units,
        public ?string $description = null,
    ) {
    }

    /**
     * @param array<mixed> $source
     */
    public static function fromArray(array $source): self
    {
        $category = PriceCategory::findOrFail((int) $source['price_category_id']);
        $price = Money::of(
            $source['price'],
            $category->getCurrency()->value,
            roundingMode: RoundingMode::UP
        )->getMinorAmount()->toInt();

        return new self(
            (int) $category->id,
            $price,
            (float) $source['amount'],
            $source['units'],
            $source['description'] ?? null,
        );
    }
}
