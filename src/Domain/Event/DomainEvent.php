<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Event;

use Psr\EventDispatcher\StoppableEventInterface;

interface DomainEvent extends StoppableEventInterface
{
    /**
     * Get the date and time when the event occurred
     */
    public function occurredOn(): \DateTimeImmutable;
}
