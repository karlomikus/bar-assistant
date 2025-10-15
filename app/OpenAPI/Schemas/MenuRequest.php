<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['is_enabled', 'items'])]
class MenuRequest
{
    /**
     * @param array<MenuItemRequest> $items
     */
    public function __construct(
        #[OAT\Property(property: 'is_enabled')]
        public bool $isEnabled = false,
        #[OAT\Property(items: new OAT\Items(type: MenuItemRequest::class))]
        public array $items = [],
    ) {
    }

    public static function fromIlluminateRequest(Request $request): self
    {
        /** @var array<mixed> */
        $formItems = $request->post('items', []);

        $items = [];
        foreach ($formItems as $formItem) {
            $items[] = MenuItemRequest::fromArray($formItem);
        }

        return new self(
            isEnabled: $request->boolean('is_enabled', false),
            items: $items,
        );
    }

    /**
     * @return array<MenuItemRequest>
     */
    public function getIngredients(): array
    {
        return array_filter($this->items, fn (MenuItemRequest $item) => $item->type === \Kami\Cocktail\Models\Enums\MenuItemTypeEnum::Ingredient);
    }

    /**
     * @return array<MenuItemRequest>
     */
    public function getCocktails(): array
    {
        return array_filter($this->items, fn (MenuItemRequest $item) => $item->type === \Kami\Cocktail\Models\Enums\MenuItemTypeEnum::Cocktail);
    }
}
