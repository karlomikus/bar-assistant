<?php

declare(strict_types=1);

namespace BarAssistant\Application\Calculator;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Calculator\Calculator;
use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Calculator\CalculatorBlock;
use BarAssistant\Domain\Calculator\CalculatorBlockType;
use BarAssistant\Domain\Calculator\CalculatorRepository;
use BarAssistant\Domain\Calculator\CalculatorBlockSettings;
use BarAssistant\Application\Calculator\DTO\SolveCalculator;
use BarAssistant\Application\Calculator\DTO\CalculatorResult;
use BarAssistant\Application\Calculator\DTO\CreateCalculator;
use BarAssistant\Application\Calculator\DTO\UpdateCalculator;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Calculator\DTO\CalculatorSolveResult;
use BarAssistant\Application\Calculator\DTO\CreateCalculatorBlock;

final readonly class CalculatorService
{
    public function __construct(
        private CalculatorRepository $calculatorRepository,
    ) {
    }

    public function createCalculator(CreateCalculator $request): CalculatorResult
    {
        $barId = new BarId($request->barId);

        $blocks = array_map(
            fn (CreateCalculatorBlock $blockData) => CalculatorBlock::create(
                label: $blockData->label,
                type: CalculatorBlockType::from($blockData->type),
                variableName: $blockData->variableName,
                value: $blockData->value,
                sort: $blockData->sort,
                description: $blockData->description,
                settings: CalculatorBlockSettings::fromArray($blockData->settings),
            ),
            $request->blocks,
        );

        $calculator = Calculator::create(
            barId: $barId,
            name: $request->name,
            description: $request->description,
            recordTimestamps: RecordTimestamps::createdNow(),
            blocks: $blocks,
        );

        $calculator = $this->calculatorRepository->save($calculator);

        return new CalculatorResult(id: $calculator->getId()->value ?? 0);
    }

    public function updateCalculator(UpdateCalculator $request): Calculator
    {
        $calculator = $this->calculatorRepository->findById(new CalculatorId($request->calculatorId));
        if ($calculator === null) {
            throw new EntityNotFoundException('The calculator to update was not found');
        }

        $blocks = array_map(
            fn (object $blockData) => CalculatorBlock::create(
                label: $blockData->label,
                type: CalculatorBlockType::from($blockData->type),
                variableName: $blockData->variableName,
                value: $blockData->value,
                sort: $blockData->sort,
                description: $blockData->description,
                settings: CalculatorBlockSettings::fromArray($blockData->settings),
            ),
            $request->blocks,
        );

        $calculator = $calculator->setName($request->name);
        $calculator = $calculator->setDescription($request->description);
        $calculator = $calculator->replaceBlocks($blocks);

        return $this->calculatorRepository->save($calculator);
    }

    public function deleteCalculator(int $calculatorId): void
    {
        $id = new CalculatorId($calculatorId);
        $calculator = $this->calculatorRepository->findById($id);

        if ($calculator === null) {
            throw new EntityNotFoundException('The calculator to delete was not found');
        }

        $this->calculatorRepository->delete($id);
    }

    public function solveCalculator(SolveCalculator $request): CalculatorSolveResult
    {
        $calculator = $this->calculatorRepository->findById(new CalculatorId($request->calculatorId));
        if ($calculator === null) {
            throw new EntityNotFoundException('The calculator was not found');
        }

        $result = $calculator->solve($request->inputs);

        return new CalculatorSolveResult(
            inputs: $result->inputs,
            results: $result->results,
        );
    }
}
