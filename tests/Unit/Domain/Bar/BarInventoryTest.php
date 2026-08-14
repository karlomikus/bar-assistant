<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Bar;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\BarInventory;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Bar\IngredientInventoryStatus;

final class BarInventoryTest extends TestCase
{
    public function test_put_ingredient_replaces_existing_status(): void
    {
        $inventory = BarInventory::create(new BarId(1));
        $ingredientId = new IngredientId(10);

        $inventory->putIngredient($ingredientId, IngredientInventoryStatus::Variant);
        $inventory->putIngredient($ingredientId, IngredientInventoryStatus::InStock);

        $ingredients = $inventory->getIngredients();

        $this->assertCount(1, $ingredients);
        $this->assertTrue($ingredients[0]->ingredientId->equals($ingredientId));
        $this->assertTrue($ingredients[0]->isInStock());
    }

    public function test_inventory_filters_return_only_matching_statuses(): void
    {
        $inventory = BarInventory::create(new BarId(1));

        $inventory->putIngredient(new IngredientId(10), IngredientInventoryStatus::InStock);
        $inventory->putIngredient(new IngredientId(20), IngredientInventoryStatus::Variant);
        $inventory->putIngredient(new IngredientId(30), IngredientInventoryStatus::Makeable);

        $inStock = $inventory->getInStockIngredients();
        $variants = $inventory->getVariantIngredients();

        $this->assertCount(1, $inStock);
        $this->assertCount(1, $variants);
        $this->assertSame(10, $inStock[0]->ingredientId->value);
        $this->assertSame(20, $variants[0]->ingredientId->value);
    }

    public function test_remove_ingredient_is_noop_when_missing(): void
    {
        $inventory = BarInventory::create(new BarId(1));

        $inventory->putIngredient(new IngredientId(10), IngredientInventoryStatus::InStock);
        $inventory->removeIngredient(new IngredientId(999));

        $ingredients = $inventory->getIngredients();

        $this->assertCount(1, $ingredients);
        $this->assertSame(10, $ingredients[0]->ingredientId->value);
    }
}
