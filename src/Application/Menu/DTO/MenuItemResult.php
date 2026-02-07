<?php

declare(strict_types=1);

namespace BarAssistant\Application\Menu\DTO;

use BarAssistant\Domain\Menu\MenuItem;

final readonly class MenuItemResult
{
    public function __construct(
        public ?int $cocktailId,
        public ?int $ingredientId,
        public int $priceMinor,
        public string $priceCurrency,
        public string $currency,
        public int $sortIndex,
        public bool $isBarInventoryAware,
    ) {
    }

    public static function fromMenuItem(MenuItem $item): self
    {
        return new self(
            cocktailId: $item->getCocktailId()?->value,
            ingredientId: $item->getIngredientId()?->value,
            priceMinor: $item->getPrice()->getAsMinor(),
            priceCurrency: $item->getPrice()->getCurrency(),
            currency: $item->getPrice()->getCurrency(),
            sortIndex: $item->getSortIndex(),
            isBarInventoryAware: $item->isBarInventoryAware(),
        );
    }
}
