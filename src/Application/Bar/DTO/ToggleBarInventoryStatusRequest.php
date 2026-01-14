<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

readonly class ToggleBarInventoryStatusRequest
{
    /**
     * @param int[] $ingredientIds
     */
    public function __construct(
        public int $barId,
        public array $ingredientIds,
    ) {
    }
}
