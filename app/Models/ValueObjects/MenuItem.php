<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\ValueObjects;

use Kami\Cocktail\Models\MenuCocktail;
use Kami\Cocktail\Models\MenuIngredient;
use Kami\Cocktail\Models\Enums\MenuItemTypeEnum;

readonly class MenuItem
{
    private function __construct(
        public int $id,
        public MenuItemTypeEnum $type,
        public int $sort,
        public Price $price,
        public string $name,
        public ?string $description = null,
        public ?string $publicId = null,
        public ?string $image = null,
        public bool $inShelf = false,
        public bool $isBarInventoryAware = false,
    ) {
    }

    public static function fromMenuCocktail(MenuCocktail $menuCocktail): self
    {
        $thumbnail = $menuCocktail->cocktail->getMainImageThumbUrl(false);

        return new self(
            id: $menuCocktail->cocktail_id,
            type: MenuItemTypeEnum::Cocktail,
            sort: $menuCocktail->sort,
            price: Price::fromMoney($menuCocktail->getMoney()),
            name: $menuCocktail->cocktail->name,
            description: $menuCocktail->cocktail->getIngredientNames()->implode(', '),
            publicId: $menuCocktail->cocktail->public_id,
            image: $thumbnail,
            inShelf: $menuCocktail->cocktail->inBarShelf(),
            isBarInventoryAware: $menuCocktail->is_bar_inventory_aware,
        );
    }

    public static function fromMenuIngredient(MenuIngredient $menuIngredient): self
    {
        $thumbnail = $menuIngredient->ingredient->getMainImageThumbUrl(false);

        return new self(
            id: $menuIngredient->ingredient_id,
            type: MenuItemTypeEnum::Ingredient,
            sort: $menuIngredient->sort,
            price: Price::fromMoney($menuIngredient->getMoney()),
            name: $menuIngredient->ingredient->name,
            description: $menuIngredient->ingredient->getMaterializedPathAsString(),
            publicId: null,
            image: $thumbnail,
            inShelf: $menuIngredient->ingredient->barHasInShelf(),
            isBarInventoryAware: $menuIngredient->is_bar_inventory_aware,
        );
    }
}
