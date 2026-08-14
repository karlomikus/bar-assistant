<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\DomainEventSubscriber;

use Tests\TestCase;
use BarAssistant\Domain\Bar\BarId;
use Illuminate\Support\Facades\Cache;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Cocktail\Event\CocktailUpdated;
use Kami\Cocktail\Infrastructure\DomainEventSubscriber\ClearPublicCocktailsCacheSubscriber;

final class ClearPublicCocktailsCacheSubscriberTest extends TestCase
{
    public function test_it_clears_public_cocktail_cache_for_updated_cocktail(): void
    {
        Cache::shouldReceive('forgetWildcardRedis')
            ->once()
            ->with('public_cocktails_index:15:*');

        $subscriber = new ClearPublicCocktailsCacheSubscriber();
        $subscriber->handle(new CocktailUpdated(
            barId: new BarId(15),
            cocktailId: new CocktailId(99),
        ));
    }

    public function test_it_reports_subscribed_event_name(): void
    {
        $subscriber = new ClearPublicCocktailsCacheSubscriber();

        $this->assertSame(['cocktailUpdated'], $subscriber->subscribedTo());
    }
}
