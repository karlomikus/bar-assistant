<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\User\User;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\User\UserName;
use BarAssistant\Domain\User\UserEmail;
use BarAssistant\Domain\User\UserSettings;
use BarAssistant\Domain\Common\RecordTimestamps;

final class UserTest extends TestCase
{
    public function test_from_persistence_hydrates_full_state(): void
    {
        $user = $this->reconstitutedUser();

        $this->assertFalse($user->isTransient());
        $this->assertSame(1, $user->getId()->value);
        $this->assertSame('Test Testović', $user->getName()->toString());
        $this->assertSame('test@test.localhost', $user->getEmail()->toString());
        $this->assertSame('password-hash', $user->getPassword());
        $this->assertTrue($user->isEmailVerified());
        $this->assertSame('2026-01-15 12:00:00', $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame('en', $user->getSettings()->toArray()['language']);
        $this->assertSame('2026-01-01 09:00:00', $user->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-01-10 18:30:00', $user->getUpdatedAt()?->format('Y-m-d H:i:s'));
    }

    public function test_change_password_preserves_email_verification(): void
    {
        $user = $this->reconstitutedUser();

        $user->changePassword('new-password-hash');

        $this->assertSame('new-password-hash', $user->getPassword());
        $this->assertTrue($user->isEmailVerified());
    }

    public function test_change_name_preserves_email_verification(): void
    {
        $user = $this->reconstitutedUser();

        $user->changeName(UserName::fromString('New Name'));

        $this->assertTrue($user->isEmailVerified());
    }

    private function reconstitutedUser(): User
    {
        return User::fromPersistence(
            id: new UserId(1),
            name: UserName::fromString('Test Testović'),
            email: UserEmail::fromString('test@test.localhost'),
            passwordHash: 'password-hash',
            emailVerifiedAt: new DateTimeImmutable('2026-01-15 12:00:00'),
            settings: UserSettings::fromArray([
                'language' => 'en',
                'theme' => 'dark',
            ]),
            timestamps: RecordTimestamps::createdAt(new DateTimeImmutable('2026-01-01 09:00:00'))
                ->updatedAt(new DateTimeImmutable('2026-01-10 18:30:00')),
        );
    }
}
