<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Menu;

use BarAssistant\Domain\Common\Price;

final readonly class MenuItem
{
    public function __construct(
        private Price $price,
        private int $sortIndex,
    )
    {
    }
}
