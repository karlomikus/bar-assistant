<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Enums\MenuItemTypeEnum;

#[OAT\Schema(required: ['id', 'type', 'sort', 'price', 'currency', 'is_bar_inventory_aware'])]
class MenuItemRequest
{
    public function __construct(
        #[OAT\Property(example: 1)]
        public int $id,
        #[OAT\Property()]
        public MenuItemTypeEnum $type,
        #[OAT\Property(example: 1)]
        public int $sort,
        #[OAT\Property(example: 22.52)]
        public float $price,
        #[OAT\Property(example: 'EUR', format: 'ISO 4217')]
        public string $currency,
        #[OAT\Property(property: 'is_bar_inventory_aware')]
        public bool $isBarInventoryAware = false,
    ) {
    }

    /**
     * @param array<mixed> $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            id: (int) $input['id'],
            type: MenuItemTypeEnum::from($input['type']),
            sort: (int) $input['sort'],
            price: (float) $input['price'],
            currency: $input['currency'] ?? 'EUR',
            isBarInventoryAware: $input['is_bar_inventory_aware'] ?? false,
        );
    }
}
