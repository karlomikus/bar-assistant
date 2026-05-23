<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

final readonly class UpdatePriceCategoryRequest
{
    public function __construct(
        public int $priceCategoryId,
        public string $name,
        public string $currency,
        public ?string $description = null,
    ) {
    }
}
