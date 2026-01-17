<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

final readonly class Dilution
{
    private function __construct(private float $value)
    {
    }

    public static function fromFloat(float $dilutionPercentage): self
    {
        if ($dilutionPercentage < 0.0 || $dilutionPercentage > 100.0) {
            throw new \InvalidArgumentException('Dilution must be between 0.0 and 100.0');
        }

        return new self($dilutionPercentage);
    }

    public function toFloat(): float
    {
        return $this->value;
    }

    public function toDecimal(): float
    {
        return $this->value / 100;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
