<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Calculator;

final readonly class CalculatorResult
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

    public static function empty(): self
    {
        return new self(inputs: [], results: []);
    }
}
