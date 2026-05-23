<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use BarAssistant\Domain\Common\Name;

interface MemberInventoryRepository
{
    public function save(MemberInventory $memberInventory): MemberInventory;

    public function delete(MemberInventory $memberInventory): void;

    public function findById(MemberInventoryId $memberInventoryId): ?MemberInventory;

    /**
     * @return MemberInventory[]
     */
    public function findByMemberId(MemberId $memberId): array;

    public function existsWithName(MemberId $memberId, Name $name, ?MemberInventoryId $excludeInventoryId = null): bool;
}
