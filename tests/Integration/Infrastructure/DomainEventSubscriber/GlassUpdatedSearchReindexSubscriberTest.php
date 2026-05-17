<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\DomainEventSubscriber;

use Tests\TestCase;
use BarAssistant\Domain\Cocktail\Event\GlassUpdated;
use Kami\Cocktail\Infrastructure\DomainEventSubscriber\GlassUpdatedSearchReindexSubscriber;

final class GlassUpdatedSearchReindexSubscriberTest extends TestCase
{
    public function test_it_handles_glass_updated_event_when_search_is_disabled(): void
    {
        config(['scout.driver' => null]);

        $subscriber = new GlassUpdatedSearchReindexSubscriber();
        $subscriber->handle(new GlassUpdated(
            barId: 15,
            glassId: 3,
            volume: 180.0,
            volumeUnits: 'ml',
            volumeMax: null,
        ));

        $this->assertTrue(true);
    }

    public function test_it_reports_subscribed_event_name(): void
    {
        $subscriber = new GlassUpdatedSearchReindexSubscriber();

        $this->assertSame(['glassUpdated'], $subscriber->subscribedTo());
    }
}
