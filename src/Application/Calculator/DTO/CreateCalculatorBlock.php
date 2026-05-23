<?php

declare(strict_types=1);

namespace BarAssistant\Application\Calculator\DTO;

/**
 * @param array<string, mixed> $settings
 */
final readonly class CreateCalculatorBlock
{
    /**
     * @param array<string, int|string> $settings
     */
    public function __construct(
        public string $label,
        public string $variableName,
        public string $value,
        public string $type,
        public array $settings = [],
        public ?string $description = null,
        public int $sort = 0,
    ) {
    }
}
