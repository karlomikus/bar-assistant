<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use BarAssistant\Domain\Common\Name;
use Kami\Cocktail\Models\Ingredient;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberInventory;
use BarAssistant\Domain\Ingredient\IngredientId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use BarAssistant\Domain\Bar\IngredientInventoryStatus;
use Kami\Cocktail\Infrastructure\EloquentMemberInventoryRepository;

final class EloquentMemberInventoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_and_finds_member_inventory(): void
    {
        $membership = $this->setupBarMembership();
        $firstIngredient = Ingredient::factory()->for($membership->bar)->create();
        $secondIngredient = Ingredient::factory()->for($membership->bar)->create();

        $memberInventory = MemberInventory::create(
            memberId: new MemberId($membership->id),
            name: Name::fromString('Back Bar'),
        );
        $memberInventory->putIngredient(new IngredientId($firstIngredient->id), IngredientInventoryStatus::InStock);
        $memberInventory->putIngredient(new IngredientId($secondIngredient->id), IngredientInventoryStatus::InStock);

        $repository = new EloquentMemberInventoryRepository();
        $savedInventory = $repository->save($memberInventory);

        $this->assertNotNull($savedInventory->getId());
        $this->assertDatabaseHas('member_inventories', [
            'id' => $savedInventory->getId()?->value,
            'bar_membership_id' => $membership->id,
            'name' => 'Back Bar',
        ]);
        $this->assertDatabaseHas('member_inventory_ingredients', [
            'member_inventory_id' => $savedInventory->getId()?->value,
            'ingredient_id' => $firstIngredient->id,
        ]);
        $this->assertDatabaseHas('member_inventory_ingredients', [
            'member_inventory_id' => $savedInventory->getId()?->value,
            'ingredient_id' => $secondIngredient->id,
        ]);

        $foundInventory = $repository->findById($savedInventory->getId() ?? new \BarAssistant\Domain\Bar\MemberInventoryId(0));

        $this->assertNotNull($foundInventory);
        $this->assertSame('Back Bar', $foundInventory->getName()->toString());
        $this->assertCount(2, $foundInventory->getIngredients());
    }

    public function test_it_replaces_existing_ingredients_and_handles_name_lookups(): void
    {
        $membership = $this->setupBarMembership();
        $removedIngredient = Ingredient::factory()->for($membership->bar)->create();
        $keptIngredient = Ingredient::factory()->for($membership->bar)->create();
        $addedIngredient = Ingredient::factory()->for($membership->bar)->create();
        $repository = new EloquentMemberInventoryRepository();

        $savedInventory = $repository->save(
            MemberInventory::create(
                memberId: new MemberId($membership->id),
                name: Name::fromString('Shelf A'),
            )
                ->putIngredient(new IngredientId($removedIngredient->id), IngredientInventoryStatus::InStock)
                ->putIngredient(new IngredientId($keptIngredient->id), IngredientInventoryStatus::InStock),
        );

        $updatedInventory = MemberInventory::create(
            memberId: new MemberId($membership->id),
            name: Name::fromString('Shelf A'),
        )->setId($savedInventory->getId() ?? new \BarAssistant\Domain\Bar\MemberInventoryId(0));
        $updatedInventory->putIngredient(new IngredientId($keptIngredient->id), IngredientInventoryStatus::InStock);
        $updatedInventory->putIngredient(new IngredientId($addedIngredient->id), IngredientInventoryStatus::InStock);

        $repository->save($updatedInventory);

        $this->assertDatabaseMissing('member_inventory_ingredients', [
            'member_inventory_id' => $savedInventory->getId()?->value,
            'ingredient_id' => $removedIngredient->id,
        ]);
        $this->assertDatabaseHas('member_inventory_ingredients', [
            'member_inventory_id' => $savedInventory->getId()?->value,
            'ingredient_id' => $keptIngredient->id,
        ]);
        $this->assertDatabaseHas('member_inventory_ingredients', [
            'member_inventory_id' => $savedInventory->getId()?->value,
            'ingredient_id' => $addedIngredient->id,
        ]);
        $this->assertTrue($repository->existsWithName(new MemberId($membership->id), Name::fromString('Shelf A')));
        $this->assertFalse($repository->existsWithName(
            new MemberId($membership->id),
            Name::fromString('Shelf A'),
            $savedInventory->getId(),
        ));

        $repository->delete($updatedInventory);

        $this->assertDatabaseMissing('member_inventories', ['id' => $savedInventory->getId()?->value]);
    }
}
