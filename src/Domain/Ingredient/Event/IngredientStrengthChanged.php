<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient\Event;

use DateTimeImmutable;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\DomainEventName;
use BarAssistant\Domain\Ingredient\IngredientId;

#[DomainEventName('ingredientStrengthChanged')]
final readonly class IngredientStrengthChanged implements DomainEvent
{
    public function __construct(public BarId $barId, public IngredientId $ingredientId, public ?float $oldStrength, public ?float $newStrength)
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
