<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Menu;

use BarAssistant\Domain\Identifier;
use Stringable;

final readonly class MenuId implements Identifier, Stringable
{
    public function __construct(public string $value)
    {
    }

    public function equals(Identifier $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
