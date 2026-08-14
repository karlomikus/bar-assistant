<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

readonly class MemberInventoryStockChangeRequest
{
    /**
     * @param int[] $ingredientIds
     */
    public function __construct(
        public array $ingredientIds,
        public ?int $memberId = null,
        public ?int $inventoryId = null,
    ) {
    }
}
