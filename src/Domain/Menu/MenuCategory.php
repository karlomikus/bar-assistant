<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Menu;

final readonly class MenuCategory
{
    public function __construct(
        private string $name,
        private array $menuItems,
    )
    {
    }
}
