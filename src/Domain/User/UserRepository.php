<?php

declare(strict_types=1);

namespace BarAssistant\Domain\User;

interface UserRepository
{
    public function findById(UserId $id): ?User;

    public function findByEmail(UserEmail $email): ?User;

    public function save(User $user): User;

    public function delete(User $user): void;
}
