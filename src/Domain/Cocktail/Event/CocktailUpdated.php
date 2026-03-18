<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail\Event;

use DateTimeImmutable;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Event\DomainEventName;

#[DomainEventName('cocktailUpdated')]
final readonly class CocktailUpdated implements DomainEvent
{
    public function __construct(
        public BarId $barId,
        public CocktailId $cocktailId,
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
