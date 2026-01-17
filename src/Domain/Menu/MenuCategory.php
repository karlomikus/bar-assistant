<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Menu;

use BarAssistant\Domain\Common\Name;

final readonly class MenuCategory
{
    public function __construct(
        private Name $name,
        private array $menuItems,
    )
    {
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getMenuItems(): array
    {
        return $this->menuItems;
    }
}
