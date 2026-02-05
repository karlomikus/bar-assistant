<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Menu\Menu;
use BarAssistant\Domain\Menu\MenuId;
use BarAssistant\Domain\Menu\MenuRepository;

/**
 * In-memory implementation of MenuRepository for testing purposes
 */
final class InMemoryMenuRepository implements MenuRepository
{
    /**
     * @param array<string, Menu> $menus
     */
    public function __construct(private array $menus = [])
    {
    }

    public function findById(MenuId $id): ?Menu
    {
        return $this->menus[$id->value] ?? null;
    }

    public function findByBarId(BarId $barId): ?Menu
    {
        foreach ($this->menus as $menu) {
            if ($menu->getBarId()->equals($barId)) {
                return $menu;
            }
        }

        return null;
    }

    public function save(Menu $menu): Menu
    {
        $this->menus[$menu->getId()->value] = $menu;

        return $menu;
    }

    public function delete(MenuId $id): void
    {
        unset($this->menus[$id->value]);
    }

    /**
     * Get all stored menus (useful for testing)
     *
     * @return Menu[]
     */
    public function getAll(): array
    {
        return array_values($this->menus);
    }
}
