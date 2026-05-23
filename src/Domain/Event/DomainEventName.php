<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Event;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DomainEventName
{
    public function __construct(public string $name)
    {
    }
}
