<?php

declare(strict_types=1);

namespace BarAssistant\Application\Menu\DTO;

/**
 * @param CreateMenuItemRequest[] $items
 */
final readonly class CreateMenuCategoryRequest
{
    public function __construct(
        public string $name = '',
        public int $sortIndex = 0,
        public array $items = [],
    ) {
    }
}
