<?php

declare(strict_types=1);

namespace BarAssistant\Application\Calculator\DTO;

/**
 * @param CreateCalculatorBlock[] $blocks
 */
final readonly class UpdateCalculator
{
    /**
     * @param array<CreateCalculatorBlock> $blocks
     */
    public function __construct(
        public int $calculatorId,
        public string $name,
        public ?string $description = null,
        public array $blocks = [],
    ) {
    }
}
