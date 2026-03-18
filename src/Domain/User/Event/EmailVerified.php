<?php

declare(strict_types=1);

namespace BarAssistant\Domain\User\Event;

use DateTimeImmutable;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\User\UserEmail;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\DomainEventName;

#[DomainEventName('emailVerified')]
final readonly class EmailVerified implements DomainEvent
{
    public function __construct(
        public UserId $userId,
        public UserEmail $email,
        public DateTimeImmutable $verifiedAt,
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
