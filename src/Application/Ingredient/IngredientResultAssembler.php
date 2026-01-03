<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient;

use BarAssistant\Application\Ingredient\DTO\IngredientPriceResult;
use BarAssistant\Application\Ingredient\DTO\IngredientResult;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientPrice;

interface IngredientResultAssembler
{
    public function toResult(Ingredient $ingredient): IngredientResult;

    public function toPriceResult(IngredientPrice $price): IngredientPriceResult;
}
