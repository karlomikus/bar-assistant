<?php
declare(strict_types=1);

namespace BarAssistant\Domain\Menu;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\MenuId;

final readonly class Menu
{
    /**
     * @param MenuCategory[] $categories
     */
    private function __construct(
        private MenuId $menuId,
        private BarId $barId,
        private array $categories,
    )
    {
    }
}
