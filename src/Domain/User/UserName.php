<?php

declare(strict_types=1);

namespace BarAssistant\Domain\User;

use Stringable;
use JsonSerializable;
use BarAssistant\Domain\Exception\DomainException;

final readonly class UserName implements Stringable, JsonSerializable
{
    private string $value;

    private function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new DomainException('User name cannot be empty');
        }

        if (mb_strlen($value) > 255) {
            throw new DomainException('User name cannot exceed 255 characters');
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

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
