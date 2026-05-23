<?php

declare(strict_types=1);

namespace BarAssistant\Application\Menu\DTO;

final readonly class UpdateMenuCategoryRequest
{
    public function __construct(
        public string $menuId = '',
        public int $categoryIndex = 0,
        public string|null $name = null,
        public int|null $sortIndex = null,
    ) {
    }
}
