<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

readonly class MemberShoppingListChangeRequest
{
    public function __construct(
        public int $memberId,
        public int $ingredientId,
        public int $quantity,
    ) {
    }
}
