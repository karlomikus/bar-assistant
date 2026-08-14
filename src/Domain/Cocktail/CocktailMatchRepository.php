<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Bar\BarId;

interface CocktailMatchRepository
{
    /**
     * @return CocktailMatch[]
     */
    public function findManyByBarId(BarId $barId): array;
}
