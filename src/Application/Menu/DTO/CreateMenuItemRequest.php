<?php

declare(strict_types=1);

namespace BarAssistant\Application\Menu\DTO;

final readonly class CreateMenuItemRequest
{
    public function __construct(
        public int|null $cocktailId = null,
        public int|null $ingredientId = null,
        public int $priceMinor = 0,
        public string $priceCurrency = 'USD',
        public int $sortIndex = 0,
    ) {
    }
}
