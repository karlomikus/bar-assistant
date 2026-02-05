<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Menu;

use BarAssistant\Domain\Bar\BarId;

interface MenuRepository
{
    /**
     * Find a menu by menu ID
     */
    public function findById(MenuId $id): ?Menu;

    /**
     * Find a menu by bar ID
     */
    public function findByBarId(BarId $barId): ?Menu;

    /**
     * Save menu to repository
     */
    public function save(Menu $menu): Menu;
}
