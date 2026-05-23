<?php

declare(strict_types=1);

namespace BarAssistant\Domain\User;

use DomainException;
use DateTimeImmutable;
use SensitiveParameter;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\DomainEventDispatcher;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\User\Event\UserAnonymized;
use BarAssistant\Domain\User\Event\UserProfileUpdated;

final class User implements Identity
{
    private ?UserId $id = null;

    private function __construct(
        private UserName $name,
        #[SensitiveParameter]
        private UserEmail $email,
        #[SensitiveParameter]
        private string $passwordHash,
        private ?DateTimeImmutable $emailVerifiedAt,
        private UserSettings $settings,
        private RecordTimestamps $timestamps,
    ) {
    }

    public static function create(
        UserName $name,
        #[SensitiveParameter]
        UserEmail $email,
        #[SensitiveParameter]
        string $passwordHash,
        ?UserSettings $settings = null,
    ): self {
        return new self(
            name: $name,
            email: $email,
            passwordHash: $passwordHash,
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

    public function getPassword(): string
    {
        return $this->passwordHash;
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
        $this->name = $name;
        $this->timestamps = $this->timestamps->updatedNow();

        return $this;
    }

    public function changeEmail(UserEmail $email): self
    {
        $oldEmail = $this->email;
        $this->email = $email;
        // $this->emailVerifiedAt = null;
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

    public function changePassword(#[SensitiveParameter] string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        $this->timestamps = $this->timestamps->updatedNow();

        return $this;
    }

    public function verifyEmail(): self
    {
        $this->emailVerifiedAt = new DateTimeImmutable();

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
        if ($this->isTransient()) {
            throw new DomainException('Cannot anonymize a transient user');
        }

        $email = $this->email;
        $this->name = UserName::fromString('Deleted User');
        $this->email = UserEmail::deletedAddress();
        $this->emailVerifiedAt = null;
        $this->passwordHash = 'deleted';
        $this->timestamps = $this->timestamps->updatedNow();

        $updatedAt = $this->timestamps->getUpdatedAt();

        $userId = $this->getId();
        if ($userId === null) {
            throw new DomainException('User ID must be set before publishing events');
        }

        DomainEventDispatcher::instance()->publish(new UserAnonymized(
            userId: $userId->value,
            originalEmail: $email->toString(),
            anonymizedAt: $updatedAt ?? new DateTimeImmutable(),
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
            && str_starts_with($this->email->toString(), 'userdeleted')
            && $this->passwordHash === 'deleted';
    }
}
