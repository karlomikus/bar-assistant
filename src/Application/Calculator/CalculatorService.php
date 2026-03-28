<?php

declare(strict_types=1);

namespace BarAssistant\Application\Calculator;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Calculator\Calculator;
use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\Calculator\CalculatorBlock;
use BarAssistant\Domain\Calculator\CalculatorBlockType;
use BarAssistant\Domain\Calculator\CalculatorRepository;
use BarAssistant\Domain\Calculator\CalculatorBlockSettings;
use BarAssistant\Application\Calculator\DTO\SolveCalculator;
use BarAssistant\Application\Calculator\DTO\CalculatorResult;
use BarAssistant\Application\Calculator\DTO\CreateCalculator;
use BarAssistant\Application\Calculator\DTO\UpdateCalculator;
use BarAssistant\Domain\Calculator\CalculatorBlockRepository;
use BarAssistant\Application\Exception\EntityNotFoundException;

final readonly class CalculatorService
{
    public function __construct(
        private CalculatorRepository $calculatorRepository,
        private CalculatorBlockRepository $calculatorBlockRepository,
    ) {
    }

    public function createCalculator(CreateCalculator $request): Calculator
    {
        $barId = new BarId($request->barId);
        $calculator = Calculator::create(
            barId: $barId,
            name: $request->name,
            description: $request->description,
        );

        foreach ($request->blocks as $blockData) {
            $block = CalculatorBlock::create(
                calculatorId: new CalculatorId(0),
                label: $blockData->label,
                type: CalculatorBlockType::fromString($blockData->type),
                variableName: $blockData->variableName,
                value: $blockData->value,
                sort: $blockData->sort,
                description: $blockData->description,
                settings: CalculatorBlockSettings::fromArray($blockData->settings),
            );
            $calculator->addBlock($block);
        }

        return $this->calculatorRepository->save($calculator);
    }

    public function updateCalculator(UpdateCalculator $request): Calculator
    {
        $calculator = $this->calculatorRepository->findById(new CalculatorId($request->calculatorId));
        if ($calculator === null) {
            throw new EntityNotFoundException('The calculator to update was not found');
        }

        $calculator = $calculator->setName($request->name);
        $calculator = $calculator->setDescription($request->description);

        foreach ($request->blocks as $blockData) {
            $block = CalculatorBlock::create(
                calculatorId: $calculator->getId() ?? new CalculatorId(0),
                label: $blockData->label,
                type: CalculatorBlockType::fromString($blockData->type),
                variableName: $blockData->variableName,
                value: $blockData->value,
                sort: $blockData->sort,
                description: $blockData->description,
                settings: CalculatorBlockSettings::fromArray($blockData->settings),
            );
            $calculator->addBlock($block);
        }

        return $this->calculatorRepository->saveWithBlocks($calculator);
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

    public function solveCalculator(SolveCalculator $request): CalculatorResult
    {
        $calculator = $this->calculatorRepository->findById(new CalculatorId($request->calculatorId));
        if ($calculator === null) {
            throw new EntityNotFoundException('The calculator was not found');
        }

        $calculatorId = $calculator->getId();
        if ($calculatorId === null) {
            throw new EntityNotFoundException('The calculator was not found');
        }

        $blocks = $this->calculatorBlockRepository->findByCalculatorId($calculatorId);
        foreach ($blocks as $block) {
            $calculator->addBlock($block);
        }

        $result = $calculator->solve($request->inputs);

        return new CalculatorResult(
            inputs: $result->inputs,
            results: $result->results,
        );
    }
}
