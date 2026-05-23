<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberInventory;
use BarAssistant\Domain\Bar\MemberInventoryId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Bar\IngredientInventoryStatus;
use BarAssistant\Domain\Bar\MemberInventoryRepository;
use BarAssistant\Application\Exception\ValidationException;
use BarAssistant\Application\Bar\DTO\DeleteInventoryRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Bar\DTO\UpdateInventoryNameRequest;
use BarAssistant\Application\Bar\DTO\CreateMemberInventoryRequest;
use BarAssistant\Application\Bar\DTO\MemberInventoryStockChangeRequest;

final readonly class MemberInventoryService
{
    public function __construct(
        private MemberInventoryRepository $memberInventoryRepository,
    ) {
    }

    public function createInventory(CreateMemberInventoryRequest $request): MemberInventory
    {
        $memberId = new MemberId($request->memberId);
        $name = Name::fromString($request->name);

        if ($this->memberInventoryRepository->existsWithName($memberId, $name)) {
            throw new ValidationException('Inventory name already exists for this member');
        }

        $memberInventory = MemberInventory::create(
            memberId: $memberId,
            name: $name,
        );

        return $this->memberInventoryRepository->save($memberInventory);
    }

    public function updateInventoryName(UpdateInventoryNameRequest $request): void
    {
        $memberInventory = $this->findInventory(new MemberInventoryId($request->inventoryId));
        $name = Name::fromString($request->name);

        if ($this->memberInventoryRepository->existsWithName(
            memberId: $memberInventory->getMemberId(),
            name: $name,
            excludeInventoryId: $memberInventory->getId(),
        )) {
            throw new ValidationException('Inventory name already exists for this member');
        }

        $memberInventory->rename(
            newName: $name,
            updatedBy: new UserId($request->userId),
        );

        $this->memberInventoryRepository->save($memberInventory);
    }

    public function deleteInventory(DeleteInventoryRequest $request): void
    {
        $memberInventory = $this->findInventory(new MemberInventoryId($request->inventoryId));

        $this->memberInventoryRepository->delete($memberInventory);
    }

    public function putMultipleIngredientsInStock(MemberInventoryStockChangeRequest $request): void
    {
        $memberInventoryId = $request->inventoryId;
        if ($memberInventoryId === null) {
            throw new ValidationException('Inventory ID is required');
        }

        $memberInventory = $this->findInventory(new MemberInventoryId($memberInventoryId));

        foreach ($request->ingredientIds as $ingredientId) {
            $memberInventory->putIngredient(new IngredientId($ingredientId), IngredientInventoryStatus::InStock);
        }

        $this->memberInventoryRepository->save($memberInventory);
    }

    public function removeMultipleIngredientsFromStock(MemberInventoryStockChangeRequest $request): void
    {
        $memberInventoryId = $request->inventoryId;
        if ($memberInventoryId === null) {
            throw new ValidationException('Inventory ID is required');
        }

        $memberInventory = $this->findInventory(new MemberInventoryId($memberInventoryId));

        foreach ($request->ingredientIds as $ingredientId) {
            $memberInventory->removeIngredient(new IngredientId($ingredientId));
        }

        $this->memberInventoryRepository->save($memberInventory);
    }

    private function findInventory(MemberInventoryId $memberInventoryId): MemberInventory
    {
        $memberInventory = $this->memberInventoryRepository->findById($memberInventoryId);
        if ($memberInventory === null || $memberInventory->isTransient()) {
            throw new EntityNotFoundException('The member inventory was not found');
        }

        return $memberInventory;
    }
}
