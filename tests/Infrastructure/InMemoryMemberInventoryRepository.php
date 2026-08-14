<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use DomainException;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberInventory;
use BarAssistant\Domain\Bar\MemberInventoryId;
use BarAssistant\Domain\Bar\MemberInventoryRepository;

final class InMemoryMemberInventoryRepository implements MemberInventoryRepository
{
    /**
     * @param array<int, MemberInventory> $memberInventories
     */
    public function __construct(private array $memberInventories = [])
    {
    }

    public function save(MemberInventory $memberInventory): MemberInventory
    {
        if ($memberInventory->isTransient()) {
            $nextId = empty($this->memberInventories) ? 1 : max(array_keys($this->memberInventories)) + 1;
            $memberInventory->setId(new MemberInventoryId($nextId));
        }

        $memberInventoryId = $memberInventory->getId();
        if ($memberInventoryId === null) {
            throw new DomainException('Member inventory ID must be set');
        }

        $this->memberInventories[$memberInventoryId->value] = $memberInventory;

        return $memberInventory;
    }

    public function delete(MemberInventory $memberInventory): void
    {
        unset($this->memberInventories[$memberInventory->getId()?->value]);
    }

    public function findById(MemberInventoryId $memberInventoryId): ?MemberInventory
    {
        return $this->memberInventories[$memberInventoryId->value] ?? null;
    }

    public function findByMemberId(MemberId $memberId): array
    {
        return array_values(array_filter(
            $this->memberInventories,
            static fn (MemberInventory $memberInventory): bool => $memberInventory->getMemberId()->equals($memberId)
        ));
    }

    public function existsWithName(MemberId $memberId, Name $name, ?MemberInventoryId $excludeInventoryId = null): bool
    {
        foreach ($this->memberInventories as $memberInventory) {
            $inventoryId = $memberInventory->getId();
            if ($inventoryId !== null && $excludeInventoryId !== null && $inventoryId->equals($excludeInventoryId)) {
                continue;
            }

            if ($memberInventory->getMemberId()->equals($memberId) && $memberInventory->getName()->equals($name)) {
                return true;
            }
        }

        return false;
    }
}
