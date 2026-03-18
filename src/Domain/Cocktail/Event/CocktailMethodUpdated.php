<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail\Event;

use DateTimeImmutable;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Cocktail\MethodId;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\DomainEventName;

#[DomainEventName('cocktailMethodUpdated')]
final readonly class CocktailMethodUpdated implements DomainEvent
{
    public function __construct(
        public BarId $barId,
        public MethodId $methodId,
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
