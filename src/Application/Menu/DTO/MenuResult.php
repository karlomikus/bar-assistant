<?php

declare(strict_types=1);

namespace BarAssistant\Application\Menu\DTO;

use DateTimeImmutable;
use BarAssistant\Domain\Menu\Menu;

final readonly class MenuResult
{
    /**
     * @param MenuCategoryResult[] $categories
     */
    private function __construct(
        public string $id,
        public int $barId,
        public bool $enabled,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
        public array $categories,
    ) {
    }

    public static function fromMenu(Menu $menu): self
    {
        $categories = [];
        foreach ($menu->getCategories() as $category) {
            $categories[] = MenuCategoryResult::fromMenuCategory($category);
        }

        $timestamps = $menu->getRecordTimestamps();

        return new self(
            id: $menu->getId()->value,
            barId: $menu->getBarId()->value,
            enabled: $menu->isEnabled(),
            createdAt: $timestamps->getCreatedAt(),
            updatedAt: $timestamps->getUpdatedAt(),
            categories: $categories,
        );
    }
}
