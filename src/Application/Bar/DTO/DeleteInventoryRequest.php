<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

final readonly class DeleteInventoryRequest
{
    public function __construct(
        public int $inventoryId,
    ) {
    }
}
