<?php

declare(strict_types=1);

namespace BarAssistant\Application\User;

use BarAssistant\Domain\User\User;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\User\UserName;
use BarAssistant\Domain\User\UserEmail;
use BarAssistant\Domain\User\UserSettings;
use BarAssistant\Domain\User\UserRepository;
use BarAssistant\Application\User\DTO\UserResult;
use BarAssistant\Application\User\DTO\UpdateUserProfile;
use BarAssistant\Application\User\DTO\ChangeEmailRequest;
use BarAssistant\Application\User\DTO\RegisterUserRequest;
use BarAssistant\Application\Exception\ValidationException;
use BarAssistant\Application\User\DTO\AnonymizeUserRequest;
use BarAssistant\Application\User\DTO\ChangePasswordRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;

final readonly class UserService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function register(RegisterUserRequest $request): UserResult
    {
        $email = UserEmail::fromString($request->email);

        $existing = $this->userRepository->findByEmail($email);
        if ($existing !== null) {
            throw new ValidationException('Email already exists');
        }

        $user = User::create(
            name: UserName::fromString($request->name),
            email: UserEmail::fromString($request->email),
            passwordHash: $request->passwordHash,
        );

        if ($request->confirmAccount === true) {
            $user->verifyEmail();
        }

        $user = $this->userRepository->save($user);

        return UserResult::fromEntity($user);
    }

    public function updateUserProfile(UpdateUserProfile $request): UserResult
    {
        $user = $this->userRepository->findById(new UserId($request->userId));
        if ($user === null || $user->isTransient()) {
            throw new EntityNotFoundException('User not found');
        }

        $user->changeName(UserName::fromString($request->name));
        $currentSettings = $user->getSettings();
        $newSettings = new UserSettings(
            language: $request->language ?? $currentSettings->language,
            theme: $request->theme ?? $currentSettings->theme,
        );

        $user->updateSettings($newSettings);

        $this->userRepository->save($user);

        return UserResult::fromEntity($user);
    }

    public function anonymizeUserAccount(AnonymizeUserRequest $request): void
    {
        $user = $this->userRepository->findById(new UserId($request->userId));
        if ($user === null || $user->isTransient()) {
            throw new EntityNotFoundException('User not found');
        }

        $user->makeAnonymous();
        $this->userRepository->save($user);
    }

    public function changePassword(ChangePasswordRequest $request): void
    {
        $user = $this->userRepository->findById(new UserId($request->userId));
        if ($user === null || $user->isTransient()) {
            throw new EntityNotFoundException('User not found');
        }

        $user->changePassword($request->newPasswordHash);
        $this->userRepository->save($user);
    }

    public function changeEmail(ChangeEmailRequest $request): void
    {
        $user = $this->userRepository->findById(new UserId($request->userId));
        if ($user === null || $user->isTransient()) {
            throw new EntityNotFoundException('User not found');
        }

        $user->changeEmail(UserEmail::fromString($request->newEmail));
        $this->userRepository->save($user);
    }
}
