<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\PriceCategory;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;

final class InMemoryPriceCategoryRepository implements PriceCategoryRepository
{
    /**
     * @param array<int, PriceCategory> $items
     */
    public function __construct(private array $items = []) {}

    public function findMany(BarId $barId, array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            $item = $this->items[$id->id] ?? null;
            if ($item !== null && $item->getBarId()->equals($barId)) {
                $result[] = $item;
            }
        }

        return $result;
    }
}
