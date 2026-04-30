<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Bar;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberInventory;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Bar\IngredientInventoryStatus;

final class MemberInventoryTest extends TestCase
{
    private function createInventory(): MemberInventory
    {
        return MemberInventory::create(
            memberId: new MemberId(1),
            name: Name::fromString('My Shelf'),
        );
    }

    public function test_put_ingredient_in_inventory_is_idempotent(): void
    {
        $inventory = $this->createInventory();

        $inventory->putIngredient(new IngredientId(100), IngredientInventoryStatus::InStock);
        $inventory->putIngredient(new IngredientId(100), IngredientInventoryStatus::InStock);

        $this->assertCount(1, $inventory->getIngredients());
        $this->assertSame(100, $inventory->getIngredients()[0]->ingredientId->value);
    }

    public function test_remove_ingredient_from_inventory_preserves_other_items(): void
    {
        $inventory = $this->createInventory();

        $inventory->putIngredient(new IngredientId(100), IngredientInventoryStatus::InStock);
        $inventory->putIngredient(new IngredientId(101), IngredientInventoryStatus::InStock);
        $inventory->putIngredient(new IngredientId(102), IngredientInventoryStatus::InStock);

        $inventory->removeIngredient(new IngredientId(101));

        $this->assertCount(2, $inventory->getIngredients());
        $this->assertSame([100, 102], array_values(array_map(
            static fn ($item): int => $item->ingredientId->value,
            $inventory->getIngredients(),
        )));
    }

    public function test_rename_updates_name_authors_and_timestamps(): void
    {
        $inventory = $this->createInventory();
        $inventory->setId(new \BarAssistant\Domain\Bar\MemberInventoryId(1));

        $inventory->rename(Name::fromString('Back Bar'), new UserId(15));

        $this->assertSame('Back Bar', $inventory->getName()->toString());
    }
}
