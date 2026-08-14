<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Calculator;

interface CalculatorRepository
{
    public function findById(CalculatorId $id): ?Calculator;

    public function save(Calculator $calculator): Calculator;

    public function delete(CalculatorId $id): void;
}
