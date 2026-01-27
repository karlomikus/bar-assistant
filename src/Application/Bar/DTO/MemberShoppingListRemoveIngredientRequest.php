<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

readonly class MemberShoppingListRemoveIngredientRequest
{
    /**
     * @param int[] $ingredientIds
     */
    public function __construct(
        public int $memberId,
        public array $ingredientIds,
    ) {
    }
}
