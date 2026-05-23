<?php

declare(strict_types=1);

namespace BarAssistant\Application\Calculator\DTO;

final readonly class CalculatorResult
{
    public function __construct(
        public int $id,
    ) {
    }
}
