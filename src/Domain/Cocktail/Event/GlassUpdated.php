<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail\Event;

use DateTimeImmutable;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Cocktail\GlassId;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\DomainEventName;

#[DomainEventName('glassUpdated')]
final readonly class GlassUpdated implements DomainEvent
{
    public function __construct(
        public BarId $barId,
        public GlassId $glassId,
        public ?float $volume,
        public ?string $volumeUnits,
        public ?float $volumeMax,
    ) {
    }

    public function isPropagationStopped(): bool
    {
        return false;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
