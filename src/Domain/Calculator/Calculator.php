<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Calculator;

use NXP\MathExecutor;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Exception\DomainException;

final class Calculator implements Identity
{
    private ?CalculatorId $id = null;

    /**
     * @var CalculatorBlock[]
     */
    private array $blocks = [];

    /**
     * @param CalculatorBlock[] $blocks
     */
    private function __construct(
        private readonly BarId $barId,
        private string $name,
        private ?string $description,
        private RecordTimestamps $recordTimestamps,
        array $blocks = [],
    ) {
        $this->blocks = $blocks;
    }

    /**
     * @param CalculatorBlock[] $blocks
     */
    public static function create(
        BarId $barId,
        string $name,
        RecordTimestamps $recordTimestamps,
        ?string $description = null,
        array $blocks = [],
    ): self {
        return new self(
            barId: $barId,
            name: $name,
            description: $description,
            blocks: $blocks,
            recordTimestamps: $recordTimestamps,
        );
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?CalculatorId
    {
        return $this->id;
    }

    public function setId(CalculatorId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing calculator');
        }

        $this->id = $id;

        return $this;
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return CalculatorBlock[]
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * @param CalculatorBlock[] $blocks
     */
    public function replaceBlocks(array $blocks): self
    {
        $this->blocks = $blocks;

        return $this;
    }

    /**
     * @return CalculatorBlock[]
     */
    public function getInputs(): array
    {
        return array_values(
            array_filter(
                $this->blocks,
                fn (CalculatorBlock $block) => $block->getType() === CalculatorBlockType::Input
            )
        );
    }

    /**
     * @return CalculatorBlock[]
     */
    public function getEvaluations(): array
    {
        return array_values(
            array_filter(
                $this->blocks,
                fn (CalculatorBlock $block) => $block->getType() === CalculatorBlockType::Eval
            )
        );
    }

    /**
     * @param array<string, string> $inputs
     */
    public function solve(array $inputs): CalculatorResult
    {
        $executor = new MathExecutor();
        $executor->setDivisionByZeroIsZero();

        /** @var array<string, string> $resultInputs */
        $resultInputs = [];
        /** @var array<string, string> $resultValues */
        $resultValues = [];

        foreach ($this->getInputs() as $block) {
            $variableName = $block->getVariableName();
            $value = $inputs[$variableName] ?? $block->getValue();
            $resultInputs[$variableName] = $value;

            $executor->setVar($variableName, $value);
        }

        foreach ($this->getEvaluations() as $block) {
            $variableName = $block->getVariableName();
            $settings = $block->getSettings();
            $result = $executor->execute($block->getValue());
            $formattedResult = number_format(floatval($result), $settings->decimalPlaces ?? 0);
            $formattedResult = trim(
                sprintf(
                    '%s %s %s',
                    $settings->prefix ?? '',
                    $formattedResult,
                    $settings->suffix ?? ''
                )
            );

            $resultValues[$variableName] = $formattedResult;

            $executor->setVar($variableName, $result);
        }

        return new CalculatorResult(
            inputs: $resultInputs,
            results: $resultValues,
        );
    }
}
