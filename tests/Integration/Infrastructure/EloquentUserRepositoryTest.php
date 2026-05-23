<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use BarAssistant\Domain\User\User;
use Illuminate\Support\Facades\Hash;
use BarAssistant\Domain\User\UserName;
use BarAssistant\Domain\User\UserEmail;
use BarAssistant\Domain\User\UserSettings;
use Kami\Cocktail\Models\User as ModelsUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentUserRepository;

final class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_verified_user(): void
    {
        $user = User::create(
            name: UserName::fromString('Test Testović'),
            email: UserEmail::fromString('test@test.localhost'),
            settings: UserSettings::fromArray([
                'language' => 'en',
                'theme' => 'dark',
            ]),
            passwordHash: Hash::make('12345'),
        );
        $user->verifyEmail();

        $repository = new EloquentUserRepository();
        $savedUser = $repository->save($user);

        $this->assertNotNull($savedUser->getId());
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'name' => 'Test Testović',
            'email' => 'test@test.localhost',
        ]);

        $model = ModelsUser::find($savedUser->getId()->value);
        $this->assertNotNull($model->password);
        $this->assertNotNull($model->email_verified_at);
        $this->assertNull($model->remember_token);
        $this->assertNotNull($model->settings);
        $this->assertTrue(Hash::check('12345', $model->password));
        $this->assertSame('en', $model->settings['language']);
        $this->assertSame('dark', $model->settings['theme']);
    }

    public function test_it_saves_unverified_user(): void
    {
        $user = User::create(
            name: UserName::fromString('Test Testović'),
            email: UserEmail::fromString('test@test.localhost'),
            settings: UserSettings::default(),
            passwordHash: Hash::make('12345'),
        );

        $repository = new EloquentUserRepository();
        $savedUser = $repository->save($user);

        $model = ModelsUser::find($savedUser->getId()->value);
        $this->assertNull($model->email_verified_at);
    }
}
