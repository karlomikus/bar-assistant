<?php

declare(strict_types=1);

namespace BarAssistant\Application\Calculator\DTO;

/**
 * @param array<string, string> $inputs
 * @param array<string, string> $results
 */
final readonly class CalculatorSolveResult
{
    /**
     * @param array<string, string> $inputs
     * @param array<string, string> $results
     */
    public function __construct(
        public array $inputs,
        public array $results,
    ) {
    }
}
