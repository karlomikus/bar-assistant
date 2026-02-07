<?php

declare(strict_types=1);

namespace BarAssistant\Application\Menu\DTO;

use BarAssistant\Domain\Menu\MenuCategory;

final readonly class MenuCategoryResult
{
    /**
     * @param MenuItemResult[] $items
     */
    private function __construct(
        public string $name,
        public int $sortIndex,
        public array $items,
    ) {
    }

    public static function fromMenuCategory(MenuCategory $category): self
    {
        $items = [];
        foreach ($category->getItems() as $item) {
            $items[] = MenuItemResult::fromMenuItem($item);
        }

        return new self(
            name: $category->getName()->toString(),
            sortIndex: $category->getSortIndex(),
            items: $items,
        );
    }
}
