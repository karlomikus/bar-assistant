<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

final readonly class CreatePriceCategoryRequest
{
    public function __construct(
        public int $barId,
        public string $name,
        public string $currency,
        public ?string $description = null,
    ) {
    }
}
