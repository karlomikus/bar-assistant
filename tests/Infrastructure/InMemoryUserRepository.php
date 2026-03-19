<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\User\User;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\User\UserName;
use BarAssistant\Domain\User\UserEmail;
use BarAssistant\Domain\User\UserSettings;
use BarAssistant\Domain\User\UserRepository;

final class InMemoryUserRepository implements UserRepository
{
    /**
     * @param array<int, User> $users
     */
    public function __construct(private array $users = [])
    {
    }

    public function findById(UserId $id): ?User
    {
        return $this->users[$id->value] ?? null;
    }

    public function findByEmail(UserEmail $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getEmail()->equals($email)) {
                return $user;
            }
        }

        return null;
    }

    public function save(User $user): User
    {
        if ($user->isTransient()) {
            $nextId = empty($this->users) ? 1 : max(array_keys($this->users)) + 1;
            $user->setId(new UserId($nextId));
        }

        $userId = $user->getId();
        if ($userId === null) {
            throw new \DomainException('User ID must be set');
        }

        $this->users[$userId->value] = $user;

        return $user;
    }

    public function delete(User $user): void
    {
        unset($this->users[$user->getId()->value]);
    }

    public function createWithPassword(
        UserName $name,
        UserEmail $email,
        string $passwordHash,
        UserSettings $settings,
        bool $emailVerified,
    ): User {
        $existing = $this->findByEmail($email);
        if ($existing !== null) {
            throw new \RuntimeException('Email already exists');
        }

        $user = User::create(
            name: $name,
            email: $email,
            settings: $settings,
        );

        if ($emailVerified) {
            $user->verifyEmail();
        }

        $this->save($user);

        return $user;
    }
}
