<?php

declare(strict_types=1);

namespace BarAssistant\Domain\User;

use DomainException;
use DateTimeImmutable;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\DomainEventDispatcher;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\User\Event\EmailVerified;
use BarAssistant\Domain\User\Event\UserAnonymized;
use BarAssistant\Domain\User\Event\UserProfileUpdated;

final class User implements Identity
{
    private ?UserId $id = null;

    private function __construct(
        private UserName $name,
        private UserEmail $email,
        private ?DateTimeImmutable $emailVerifiedAt,
        private UserSettings $settings,
        private RecordTimestamps $timestamps,
    ) {
    }

    public static function create(
        UserName $name,
        UserEmail $email,
        ?UserSettings $settings = null,
    ): self {
        return new self(
            name: $name,
            email: $email,
            emailVerifiedAt: null,
            settings: $settings ?? UserSettings::default(),
            timestamps: RecordTimestamps::createdNow(),
        );
    }

    public function getId(): ?UserId
    {
        return $this->id;
    }

    public function setId(UserId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing user');
        }

        $this->id = $id;

        return $this;
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getName(): UserName
    {
        return $this->name;
    }

    public function getEmail(): UserEmail
    {
        return $this->email;
    }

    public function getEmailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function getSettings(): UserSettings
    {
        return $this->settings;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->timestamps->getCreatedAt();
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->timestamps->getUpdatedAt();
    }

    public function changeName(UserName $name): self
    {
        $oldName = $this->name;
        $this->name = $name;
        $this->timestamps = $this->timestamps->updatedNow();

        $userId = $this->getId();
        if ($userId === null) {
            throw new DomainException('User ID must be set before publishing events');
        }

        DomainEventDispatcher::instance()->publish(new UserProfileUpdated(
            userId: $userId,
            oldName: $oldName,
            newName: $name,
            oldEmail: $this->email,
            newEmail: $this->email,
        ));

        return $this;
    }

    public function changeEmail(UserEmail $email): self
    {
        $oldEmail = $this->email;
        $this->email = $email;
        $this->emailVerifiedAt = null;
        $this->timestamps = $this->timestamps->updatedNow();

        $userId = $this->getId();
        if ($userId === null) {
            throw new DomainException('User ID must be set before publishing events');
        }

        DomainEventDispatcher::instance()->publish(new UserProfileUpdated(
            userId: $userId,
            oldName: $this->name,
            newName: $this->name,
            oldEmail: $oldEmail,
            newEmail: $email,
        ));

        return $this;
    }

    public function verifyEmail(): self
    {
        if ($this->isTransient()) {
            throw new DomainException('Cannot verify email of a transient user');
        }

        $this->emailVerifiedAt = new DateTimeImmutable();

        DomainEventDispatcher::instance()->publish(new EmailVerified(
            userId: $this->getId(),
            email: $this->email,
            verifiedAt: $this->emailVerifiedAt,
        ));

        return $this;
    }

    public function updateSettings(UserSettings $settings): self
    {
        $this->settings = $settings;
        $this->timestamps = $this->timestamps->updatedNow();

        return $this;
    }

    public function makeAnonymous(): self
    {
        $this->name = UserName::fromString('Deleted User');
        $this->email = UserEmail::fromString('userdeleted' . bin2hex(random_bytes(4)));
        $this->emailVerifiedAt = null;
        $this->timestamps = $this->timestamps->updatedNow();

        $userId = $this->getId();
        $updatedAt = $this->timestamps->getUpdatedAt();
        if ($userId === null || $updatedAt === null) {
            throw new DomainException('User ID and updated timestamp must be set before publishing events');
        }

        DomainEventDispatcher::instance()->publish(new UserAnonymized(
            userId: $userId,
            anonymizedAt: $updatedAt,
        ));

        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function isAnonymous(): bool
    {
        return $this->name->toString() === 'Deleted User'
            && str_starts_with($this->email->toString(), 'userdeleted');
    }
}
