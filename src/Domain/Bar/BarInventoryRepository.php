<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

interface BarInventoryRepository
{
    public function findByBarId(BarId $barId): ?BarInventory;

    public function save(BarInventory $barInventory): BarInventory;
}
