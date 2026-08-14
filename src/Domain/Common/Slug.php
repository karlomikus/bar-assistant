<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

use Stringable;
use JsonSerializable;
use BarAssistant\Domain\Exception\DomainException;

final readonly class Slug implements Stringable, JsonSerializable
{
    private string $value;

    private function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new DomainException('Slug cannot be empty');
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            throw new DomainException('Slug must contain only lowercase alphanumeric characters and hyphens');
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

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
