<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Menu;

use Stringable;
use BarAssistant\Domain\Identifier;

final readonly class MenuId implements Identifier, Stringable
{
    /**
     * Menu ID is a publicly available slug chosen by a user
     */
    public function __construct(public string $value)
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
        return $this->value;
    }
}
