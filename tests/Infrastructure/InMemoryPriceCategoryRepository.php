<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\PriceCategory;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;

final class InMemoryPriceCategoryRepository implements PriceCategoryRepository
{
    private int $nextId = 1;

    /**
     * @param array<int, PriceCategory> $items
     */
    public function __construct(private array $items = [])
    {
    }

    public function findById(PriceCategoryId $id): ?PriceCategory
    {
        return $this->items[$id->value] ?? null;
    }

    public function save(PriceCategory $priceCategory): PriceCategory
    {
        if ($priceCategory->isTransient()) {
            $priceCategory->setId(new PriceCategoryId($this->nextId++));
        }

        /** @var PriceCategoryId $id */
        $id = $priceCategory->getId();
        $this->items[$id->value] = $priceCategory;

        return $priceCategory;
    }

    public function findMany(BarId $barId, array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            $item = $this->items[$id->value] ?? null;
            if ($item !== null && $item->getBarId()->equals($barId)) {
                $result[] = $item;
            }
        }

        return $result;
    }
}
