<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Calculator;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Exception\DomainException;

final class CalculatorBlock implements Identity
{
    private ?CalculatorBlockId $id = null;

    private function __construct(
        private readonly CalculatorId $calculatorId,
        private readonly string $label,
        private readonly CalculatorBlockType $type,
        private readonly string $variableName,
        private readonly string $value,
        private readonly int $sort,
        private readonly ?string $description,
        private readonly CalculatorBlockSettings $settings,
    ) {
    }

    public static function create(
        CalculatorId $calculatorId,
        string $label,
        CalculatorBlockType $type,
        string $variableName,
        string $value,
        int $sort = 0,
        ?string $description = null,
        ?CalculatorBlockSettings $settings = null,
    ): self {
        return new self(
            calculatorId: $calculatorId,
            label: $label,
            type: $type,
            variableName: $variableName,
            value: $value,
            sort: $sort,
            description: $description,
            settings: $settings ?? CalculatorBlockSettings::empty(),
        );
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?CalculatorBlockId
    {
        return $this->id;
    }

    public function setId(CalculatorBlockId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing calculator block');
        }

        $this->id = $id;

        return $this;
    }

    public function getCalculatorId(): CalculatorId
    {
        return $this->calculatorId;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): CalculatorBlockType
    {
        return $this->type;
    }

    public function getVariableName(): string
    {
        return $this->variableName;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSettings(): CalculatorBlockSettings
    {
        return $this->settings;
    }
}
