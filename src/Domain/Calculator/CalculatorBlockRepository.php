<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Calculator;

interface CalculatorBlockRepository
{
    public function findById(CalculatorBlockId $id): ?CalculatorBlock;

    /**
     * @return CalculatorBlock[]
     */
    public function findByCalculatorId(CalculatorId $calculatorId): array;

    public function save(CalculatorBlock $block): CalculatorBlock;

    /**
     * @param CalculatorBlock[] $blocks
     * @return CalculatorBlock[]
     */
    public function saveMany(array $blocks): array;

    public function delete(CalculatorBlockId $id): void;

    public function deleteByCalculatorId(CalculatorId $calculatorId): void;
}
