<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Bar;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberInventory;
use BarAssistant\Domain\Bar\MemberInventoryId;
use BarAssistant\Application\Bar\MemberInventoryService;
use BarAssistant\Application\Exception\ValidationException;
use Tests\Infrastructure\InMemoryMemberInventoryRepository;
use BarAssistant\Application\Bar\DTO\DeleteInventoryRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Bar\DTO\UpdateInventoryNameRequest;
use BarAssistant\Application\Bar\DTO\CreateMemberInventoryRequest;
use BarAssistant\Application\Bar\DTO\MemberInventoryStockChangeRequest;

final class MemberInventoryServiceTest extends TestCase
{
    private InMemoryMemberInventoryRepository $memberInventoryRepository;
    private MemberInventoryService $service;

    protected function setUp(): void
    {
        $this->memberInventoryRepository = new InMemoryMemberInventoryRepository([
            1 => MemberInventory::create(
                memberId: new MemberId(1),
                name: Name::fromString('My Shelf'),
            )->setId(new MemberInventoryId(1)),
            2 => MemberInventory::create(
                memberId: new MemberId(1),
                name: Name::fromString('Party Shelf'),
            )->setId(new MemberInventoryId(2)),
        ]);

        $this->service = new MemberInventoryService($this->memberInventoryRepository);
    }

    public function test_create_inventory_persists_new_inventory(): void
    {
        $memberInventory = $this->service->createInventory(new CreateMemberInventoryRequest(
            memberId: 2,
            userId: 20,
            name: 'Cabinet',
        ));

        $this->assertNotNull($memberInventory->getId());
        $this->assertSame('Cabinet', $memberInventory->getName()->toString());
        $this->assertSame(2, $memberInventory->getMemberId()->value);
    }

    public function test_create_inventory_rejects_duplicate_name_within_member(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Inventory name already exists for this member');

        $this->service->createInventory(new CreateMemberInventoryRequest(
            memberId: 1,
            userId: 10,
            name: 'My Shelf',
        ));
    }

    public function test_update_inventory_name_renames_existing_inventory(): void
    {
        $this->service->updateInventoryName(new UpdateInventoryNameRequest(
            inventoryId: 1,
            userId: 11,
            name: 'Back Bar',
        ));

        $memberInventory = $this->memberInventoryRepository->findById(new MemberInventoryId(1));
        $this->assertNotNull($memberInventory);
        $this->assertSame('Back Bar', $memberInventory->getName()->toString());
    }

    public function test_put_and_remove_multiple_ingredients_updates_inventory(): void
    {
        $this->service->putMultipleIngredientsInStock(new MemberInventoryStockChangeRequest(
            ingredientIds: [100, 101, 102],
            inventoryId: 1,
        ));

        $this->service->removeMultipleIngredientsFromStock(new MemberInventoryStockChangeRequest(
            ingredientIds: [101],
            inventoryId: 1,
        ));

        $memberInventory = $this->memberInventoryRepository->findById(new MemberInventoryId(1));
        $this->assertNotNull($memberInventory);
        $this->assertSame([100, 102], array_values(array_map(
            static fn ($item): int => $item->ingredientId->value,
            $memberInventory->getIngredients(),
        )));
    }

    public function test_delete_inventory_removes_existing_inventory(): void
    {
        $this->service->deleteInventory(new DeleteInventoryRequest(inventoryId: 2));

        $this->assertNull($this->memberInventoryRepository->findById(new MemberInventoryId(2)));
    }

    public function test_stock_change_requires_existing_inventory(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('The member inventory was not found');

        $this->service->putMultipleIngredientsInStock(new MemberInventoryStockChangeRequest(
            ingredientIds: [100],
            inventoryId: 999,
        ));
    }
}
