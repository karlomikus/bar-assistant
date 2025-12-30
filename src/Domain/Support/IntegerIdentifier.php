<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Support;

use BarAssistant\Domain\AggregateRootId;

abstract readonly class IntegerIdentifier implements AggregateRootId
{
    public function __construct(public int $id)
    {
    }

    public function equals(AggregateRootId $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->id === $other->id;
    }
}
