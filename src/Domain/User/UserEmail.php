<?php

declare(strict_types=1);

namespace BarAssistant\Domain\User;

use Stringable;
use JsonSerializable;
use BarAssistant\Domain\Exception\DomainException;

final readonly class UserEmail implements Stringable, JsonSerializable
{
    private string $value;

    private function __construct(string $value)
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException('Invalid email format');
        }

        $this->value = strtolower($value);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function deletedAddress(): self
    {
        return new self('userdeleted' . bin2hex(random_bytes(4)) . '@deleted.localhost');
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
