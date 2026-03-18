<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use Brick\Money\Currency;
use BarAssistant\Domain\Common\Unit;

final readonly class BarSettings
{
    private function __construct(
        public bool $isInviteCodeEnabled = false,
        public ?Unit $defaultUnits = null,
        public ?Currency $defaultCurrency = null,
    ) {
    }

    public static function createDefault(): self
    {
        return new self();
    }

    public static function create(
        bool $isInviteCodeEnabled,
        ?Unit $defaultUnits,
        ?Currency $defaultCurrency,
    ): self {
        return new self(
            isInviteCodeEnabled: $isInviteCodeEnabled,
            defaultUnits: $defaultUnits,
            defaultCurrency: $defaultCurrency,
        );
    }
}
