<?php

declare(strict_types=1);

namespace BarAssistant\Application\User;

use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\User\UserName;
use BarAssistant\Domain\User\UserEmail;
use BarAssistant\Domain\User\UserSettings;
use BarAssistant\Domain\User\UserRepository;
use BarAssistant\Application\User\DTO\UserResult;
use BarAssistant\Application\User\DTO\UpdateUserProfile;
use BarAssistant\Application\User\DTO\ChangeEmailRequest;
use BarAssistant\Application\User\DTO\UpdateUserSettings;
use BarAssistant\Application\User\DTO\VerifyEmailRequest;
use BarAssistant\Application\User\DTO\AnonymizeUserRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;

final readonly class UserService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function updateUserProfile(UpdateUserProfile $request): UserResult
    {
        $user = $this->userRepository->findById(new UserId($request->userId));
        if ($user === null || $user->isTransient()) {
            throw new EntityNotFoundException('User not found');
        }

        $user->changeName(UserName::fromString($request->name));
        $this->userRepository->save($user);

        return UserResult::fromEntity($user);
    }

    public function changeUserEmail(ChangeEmailRequest $request): UserResult
    {
        $user = $this->userRepository->findById(new UserId($request->userId));
        if ($user === null || $user->isTransient()) {
            throw new EntityNotFoundException('User not found');
        }

        $user->changeEmail(UserEmail::fromString($request->email));
        $this->userRepository->save($user);

        return UserResult::fromEntity($user);
    }

    public function verifyUserEmail(VerifyEmailRequest $request): UserResult
    {
        $user = $this->userRepository->findById(new UserId($request->userId));
        if ($user === null || $user->isTransient()) {
            throw new EntityNotFoundException('User not found');
        }

        $user->verifyEmail();
        $this->userRepository->save($user);

        return UserResult::fromEntity($user);
    }

    public function updateUserSettings(UpdateUserSettings $request): UserResult
    {
        $user = $this->userRepository->findById(new UserId($request->userId));
        if ($user === null || $user->isTransient()) {
            throw new EntityNotFoundException('User not found');
        }

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

    public function getUserById(int $userId): UserResult
    {
        $user = $this->userRepository->findById(new UserId($userId));
        if ($user === null || $user->isTransient()) {
            throw new EntityNotFoundException('User not found');
        }

        return UserResult::fromEntity($user);
    }
}
