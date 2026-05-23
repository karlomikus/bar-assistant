<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name', 'sort', 'items'])]
class MenuCategoryRequest
{
    /**
     * @param array<MenuItemRequest> $items
     */
    public function __construct(
        #[OAT\Property(example: 1)]
        public int $sort,
        #[OAT\Property()]
        public string $name,
        #[OAT\Property(items: new OAT\Items(type: MenuItemRequest::class))]
        public array $items = [],
    ) {
    }

    /**
     * @param array<mixed> $input
     */
    public static function fromArray(array $input): self
    {
        $items = [];
        foreach ($input['items'] as $formItem) {
            $items[] = MenuItemRequest::fromArray($formItem);
        }

        return new self(
            sort: (int) $input['sort'],
            name: $input['name'],
            items: $items,
        );
    }
}
