<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;

interface IngredientMatchRepository
{
    /**
     * @return IngredientMatch[]
     */
    public function findManyByBarId(BarId $barId): array;
}
