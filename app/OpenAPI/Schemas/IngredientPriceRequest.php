<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\PriceCategory;

#[OAT\Schema(required: ['price_category_id', 'price', 'amount', 'units'])]
readonly class IngredientPriceRequest
{
    public function __construct(
        #[OAT\Property(property: 'price_category_id')]
        public int $priceCategoryId,
        #[OAT\Property()]
        public int $price,
        #[OAT\Property()]
        public float $amount,
        #[OAT\Property()]
        public string $units,
        #[OAT\Property()]
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
            $category->getCurrency(),
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
