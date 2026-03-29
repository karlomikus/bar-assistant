<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Calculator;

final readonly class CalculatorBlock
{
    private function __construct(
        private string $label,
        private CalculatorBlockType $type,
        private string $variableName,
        private string $value,
        private int $sort,
        private ?string $description,
        private CalculatorBlockSettings $settings,
    ) {
    }

    public static function create(
        string $label,
        CalculatorBlockType $type,
        string $variableName,
        string $value,
        int $sort = 0,
        ?string $description = null,
        ?CalculatorBlockSettings $settings = null,
    ): self {
        return new self(
            label: $label,
            type: $type,
            variableName: $variableName,
            value: $value,
            sort: $sort,
            description: $description,
            settings: $settings ?? CalculatorBlockSettings::default(),
        );
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
