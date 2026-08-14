<?php

declare(strict_types=1);

namespace BarAssistant\Application\Menu\DTO;

final readonly class CreateMenuRequest
{
    /**
     * @param CreateMenuCategoryRequest[] $categories
     */
    public function __construct(
        public int $barId,
        public string $menuId,
        public bool $isEnabled,
        public array $categories = [],
    ) {
    }
}
