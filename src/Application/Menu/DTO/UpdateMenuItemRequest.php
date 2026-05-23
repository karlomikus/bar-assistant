<?php

declare(strict_types=1);

namespace BarAssistant\Application\Menu\DTO;

final readonly class UpdateMenuItemRequest
{
    public function __construct(
        public int $itemIndex = 0,
        public int|null $priceMinor = null,
        public string|null $priceCurrency = null,
        public int|null $sortIndex = null,
    ) {
    }
}
