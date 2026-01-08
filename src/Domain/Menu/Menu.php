<?php
declare(strict_types=1);

namespace BarAssistant\Domain\Menu;

use BarAssistant\Domain\Bar\BarId;

final readonly class Menu
{
    public function __construct(
        private BarId $barId,
        private bool $isEnabled = false,
    )
    {
    }
}