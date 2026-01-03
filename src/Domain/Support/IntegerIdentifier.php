<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Support;

use Stringable;
use BarAssistant\Domain\Identifier;

abstract readonly class IntegerIdentifier implements Identifier, Stringable
{
    public function __construct(public int $id)
    {
    }

    public function equals(Identifier $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->id === $other->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
