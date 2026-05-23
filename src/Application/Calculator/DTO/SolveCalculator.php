<?php

declare(strict_types=1);

namespace BarAssistant\Application\Calculator\DTO;

/**
 * @param array<string, string> $inputs
 */
final readonly class SolveCalculator
{
    /**
     * @param array<string, string> $inputs
     */
    public function __construct(
        public int $calculatorId,
        public array $inputs = [],
    ) {
    }
}
