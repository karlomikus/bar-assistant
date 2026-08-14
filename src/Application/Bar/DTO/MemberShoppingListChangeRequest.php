<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

readonly class MemberShoppingListChangeRequest
{
    /**
     * @param array<int, int> $ingredientQuantities
     */
    public function __construct(
        public int $memberId,
        public array $ingredientQuantities,
    ) {
    }
}
