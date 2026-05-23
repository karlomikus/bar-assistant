<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

use BarAssistant\Domain\Ingredient\PriceCategory;

final readonly class PriceCategoryResult
{
    public function __construct(
        public int $id,
        public int $barId,
        public string $name,
        public string $currency,
        public ?string $description = null,
    ) {
    }

    public static function fromPriceCategory(PriceCategory $priceCategory): self
    {
        return new self(
            id: $priceCategory->getId()->value ?? 0,
            barId: $priceCategory->getBarId()->value,
            name: $priceCategory->getName()->toString(),
            currency: $priceCategory->getCurrency()->getCurrencyCode(),
            description: $priceCategory->getDescription(),
        );
    }
}
