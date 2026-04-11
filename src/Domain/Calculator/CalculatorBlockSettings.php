<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Calculator;

final readonly class CalculatorBlockSettings
{
    private function __construct(
        public ?string $suffix,
        public ?string $prefix,
        public ?int $decimalPlaces,
    ) {
    }

    /**
     * @param array<string, string|int> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            suffix: is_string($data['suffix'] ?? null) ? $data['suffix'] : null,
            prefix: is_string($data['prefix'] ?? null) ? $data['prefix'] : null,
            decimalPlaces: isset($data['decimal_places']) ? (int) $data['decimal_places'] : null,
        );
    }

    public static function default(): self
    {
        return new self(
            suffix: null,
            prefix: null,
            decimalPlaces: 2,
        );
    }

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(): array
    {
        return [
            'suffix' => $this->suffix,
            'prefix' => $this->prefix,
            'decimal_places' => $this->decimalPlaces,
        ];
    }
}
