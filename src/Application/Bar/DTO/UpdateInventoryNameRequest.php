<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

final readonly class UpdateInventoryNameRequest
{
    public function __construct(
        public int $inventoryId,
        public int $userId,
        public string $name,
    ) {
    }
}
