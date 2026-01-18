<?php

declare(strict_types=1);

namespace BarAssistant\Application\Menu\DTO;

/**
 * @param CreateMenuCategoryRequest[] $categories
 */
final readonly class CreateMenuRequest
{
    public function __construct(
        public int $barId = 0,
        public string $menuId = '',
        public array $categories = [],
    ) {
    }
}
