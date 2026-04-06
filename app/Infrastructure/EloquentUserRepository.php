<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\User\User;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\User\UserName;
use BarAssistant\Domain\User\UserEmail;
use BarAssistant\Domain\User\UserSettings;
use Kami\Cocktail\Models\User as UserModel;
use BarAssistant\Domain\User\UserRepository;

final class EloquentUserRepository implements UserRepository
{
    public function findById(UserId $id): ?User
    {
        $model = UserModel::find($id->value);
        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function findByEmail(UserEmail $email): ?User
    {
        $model = UserModel::where('email', $email->toString())->first();
        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function save(User $user): User
    {
        $model = UserModel::findOrNew($user->getId()?->value);

        $model->name = $user->getName()->toString();
        $model->email = $user->getEmail()->toString();
        $model->password = $user->getPassword();
        $model->email_verified_at = $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s');
        $model->settings = $user->getSettings()->toArray();
        $model->created_at = $user->getCreatedAt()->format('Y-m-d H:i:s');
        $model->updated_at = $user->getUpdatedAt()?->format('Y-m-d H:i:s');
        $model->save();

        return self::map($model);
    }

    public function delete(User $user): void
    {
        UserModel::destroy($user->getId()->value);
    }

    private static function map(UserModel $model): User
    {
        $user = User::create(
            name: UserName::fromString($model->name),
            email: UserEmail::fromString($model->email),
            settings: UserSettings::fromArray($model->settings?->toArray() ?? []),
            passwordHash: $model->password,
        )->setId(new UserId($model->id));

        return $user;
    }
}
