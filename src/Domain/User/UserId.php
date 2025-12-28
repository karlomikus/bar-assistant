<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

final readonly class UserId
{
    public function __construct(public int $id)
    {
    }
}
