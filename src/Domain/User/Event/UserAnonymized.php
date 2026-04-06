<?php

declare(strict_types=1);

namespace BarAssistant\Domain\User\Event;

use DateTimeImmutable;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\DomainEventName;

#[DomainEventName('userAnonymized')]
final readonly class UserAnonymized implements DomainEvent
{
    public function __construct(
        public int $userId,
        public string $originalEmail,
        public DateTimeImmutable $anonymizedAt,
    ) {
    }

    public function occurredOn(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    public function isPropagationStopped(): bool
    {
        return false;
    }
}
