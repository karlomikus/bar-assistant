<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;

interface PriceCategoryRepository
{
    public function findById(PriceCategoryId $id): ?PriceCategory;

    /**
     * @param BarId $barId
     * @param PriceCategoryId[] $ids
     * @return PriceCategory[]
     */
    public function findMany(BarId $barId, array $ids): array;

    /**
     * Save a price category (insert or update)
     */
    public function save(PriceCategory $priceCategory): PriceCategory;
}
