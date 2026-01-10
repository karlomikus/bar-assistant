<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Support;

use BarAssistant\Domain\Exception\DomainException;
use Stringable;

/**
 * Name value object
 *
 * Represents a validated, non-empty name string.
 */
final readonly class Name implements Stringable
{
    private string $value;

    private function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new DomainException('Name cannot be empty');
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
