<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

use Stringable;
use BarAssistant\Domain\Identifier;

abstract readonly class IntegerIdentifier implements Identifier, Stringable
{
    public function __construct(public int $value)
    {
    }

    public function equals(Identifier $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
