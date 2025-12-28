<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

final readonly class BarId
{
    public function __construct(public int $id)
    {
    }
}
