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
        private int $sortIndex,
        private array $items,
        private bool $isEnabled = true,
    ) {
    }

    public static function create(Name $name, int $sortIndex = 0, bool $isEnabled = true): self
    {
        return new self(
            name: $name,
            sortIndex: $sortIndex,
            items: [],
            isEnabled: $isEnabled,
        );
    }

    /**
     * @param MenuItem[] $items
     */
    public static function createWithItems(Name $name, int $sortIndex, array $items, bool $isEnabled = true): self
    {
        return new self(
            name: $name,
            sortIndex: $sortIndex,
            items: self::sortItems($items),
            isEnabled: $isEnabled,
        );
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getSortIndex(): int
    {
        return $this->sortIndex;
    }

    /**
     * @return MenuItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(MenuItem $item): self
    {
        $items = $this->items;
        $items[] = $item;

        return new self(
            name: $this->name,
            sortIndex: $this->sortIndex,
            items: self::sortItems($items),
            isEnabled: $this->isEnabled,
        );
    }

    public function withName(Name $name): self
    {
        return new self(
            name: $name,
            sortIndex: $this->sortIndex,
            items: $this->items,
            isEnabled: $this->isEnabled,
        );
    }

    public function withSortIndex(int $sortIndex): self
    {
        return new self(
            name: $this->name,
            sortIndex: $sortIndex,
            items: $this->items,
            isEnabled: $this->isEnabled,
        );
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function enable(): self
    {
        return new self(
            name: $this->name,
            sortIndex: $this->sortIndex,
            items: $this->items,
            isEnabled: true,
        );
    }

    public function disable(): self
    {
        return new self(
            name: $this->name,
            sortIndex: $this->sortIndex,
            items: $this->items,
            isEnabled: false,
        );
    }

    /**
     * @param MenuItem[] $items
     * @return MenuItem[]
     */
    private static function sortItems(array $items): array
    {
        usort($items, static fn (MenuItem $a, MenuItem $b) => $a->getSortIndex() <=> $b->getSortIndex());

        return $items;
    }
}
