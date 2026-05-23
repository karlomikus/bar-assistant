<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\PriceCategory;

#[OAT\Schema(required: ['price_category_id', 'price', 'amount', 'units'])]
readonly class IngredientPriceRequest
{
    public function __construct(
        #[OAT\Property(property: 'price_category_id')]
        public int $priceCategoryId,
        #[OAT\Property()]
        public float $price,
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

        return new self(
            (int) $category->id,
            (float) $source['price'],
            (float) $source['amount'],
            $source['units'],
            $source['description'] ?? null,
        );
    }
}
