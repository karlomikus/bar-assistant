<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\DomainEventSubscriber;

use Tests\TestCase;
use DateTimeImmutable;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\Mail;
use BarAssistant\Domain\Event\DomainEvent;
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
