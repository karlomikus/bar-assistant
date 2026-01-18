<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Menu;

use BarAssistant\Domain\Common\Name;

final readonly class MenuCategory
{
    /**
     * @param MenuItem[] $items
     */
    private function __construct(
        private Name $name,
        private array $items,
    )
    {
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
