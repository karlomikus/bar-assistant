<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail\Event;

use DateTimeImmutable;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\DomainEventName;

#[DomainEventName('cocktailMethodUpdated')]
final readonly class CocktailMethodUpdated implements DomainEvent
{
    public function __construct(
        public int $barId,
        public int $methodId,
        public float $previousDilutionPercentage,
        public float $currentDilutionPercentage,
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
