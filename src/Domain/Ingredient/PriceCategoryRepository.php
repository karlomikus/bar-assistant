<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;

interface PriceCategoryRepository
{
    /**
     * @param BarId $barId
     * @param PriceCategoryId[] $ids
     * @return PriceCategory[]
     */
    public function findMany(BarId $barId, array $ids): array;
}
