<?php

declare(strict_types=1);

namespace BarAssistant\Domain\User\Event;

use DateTimeImmutable;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\User\UserName;
use BarAssistant\Domain\User\UserEmail;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\DomainEventName;

#[DomainEventName('userProfileUpdated')]
final readonly class UserProfileUpdated implements DomainEvent
{
    public function __construct(
        public UserId $userId,
        public UserName $oldName,
        public UserName $newName,
        public UserEmail $oldEmail,
        public UserEmail $newEmail,
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
