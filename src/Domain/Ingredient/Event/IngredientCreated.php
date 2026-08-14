<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient\Event;

use DateTimeImmutable;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\DomainEventName;

#[DomainEventName('ingredientCreated')]
final readonly class IngredientCreated implements DomainEvent
{
    public function __construct()
    {
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
