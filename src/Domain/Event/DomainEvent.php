<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Event;

interface DomainEvent
{
    /**
     * Get the date and time when the event occurred
     */
    public function occurredOn(): \DateTimeImmutable;
}
