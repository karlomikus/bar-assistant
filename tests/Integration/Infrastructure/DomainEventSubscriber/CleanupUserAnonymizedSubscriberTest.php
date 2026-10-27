<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\DomainEventSubscriber;

use Tests\TestCase;
use DateTimeImmutable;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Kami\Cocktail\Mail\AccountDeleted;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\User\Event\UserAnonymized;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\DomainEventSubscriber\CleanupUserAnonymizedSubscriber;

final class CleanupUserAnonymizedSubscriberTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_ignores_non_matching_events(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'keep@example.com']);

        $subscriber = new CleanupUserAnonymizedSubscriber();
        $subscriber->handle($this->dummyEvent());

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'keep@example.com',
        ]);
        Mail::assertNothingQueued();
    }

    public function test_it_reports_subscribed_event_name(): void
    {
        $subscriber = new CleanupUserAnonymizedSubscriber();

        $this->assertSame(['userAnonymized'], $subscriber->subscribedTo());
    }

    public function test_it_revokes_access_tokens_and_password_reset_tokens(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'deleted@example.com']);
        $user->createToken('login');
        $user->createToken('personal', ['bars.write']);

        DB::table('password_resets')->insert([
            'email' => 'deleted@example.com',
            'token' => 'reset-token',
            'created_at' => now(),
        ]);

        $subscriber = new CleanupUserAnonymizedSubscriber();
        $subscriber->handle(new UserAnonymized(
            userId: $user->id,
            originalEmail: 'deleted@example.com',
            anonymizedAt: new DateTimeImmutable(),
        ));

        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        $this->assertDatabaseMissing('password_resets', ['email' => 'deleted@example.com']);
        Mail::assertQueued(AccountDeleted::class);
    }

    private function dummyEvent(): DomainEvent
    {
        return new class () implements DomainEvent {
            public function occurredOn(): DateTimeImmutable
            {
                return new DateTimeImmutable();
            }

            public function isPropagationStopped(): bool
            {
                return false;
            }
        };
    }
}
