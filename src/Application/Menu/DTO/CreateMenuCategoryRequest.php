<?php

declare(strict_types=1);

namespace BarAssistant\Application\Menu\DTO;

final readonly class CreateMenuCategoryRequest
{
    /**
     * @param CreateMenuItemRequest[] $items
     */
    public function __construct(
        public string $name,
        public int $sortIndex = 0,
        public array $items = [],
        public bool $isEnabled = true,
    ) {
    }
}
