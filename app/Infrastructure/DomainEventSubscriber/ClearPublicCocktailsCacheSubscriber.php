<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure\DomainEventSubscriber;

use Illuminate\Support\Facades\Cache;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\EventSubscriber;
use BarAssistant\Domain\Cocktail\Event\CocktailUpdated;

final class ClearPublicCocktailsCacheSubscriber implements EventSubscriber
{
    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof CocktailUpdated) {
            return;
        }

        Cache::forgetWildcardRedis('public_cocktails_index:' . $event->barId->value . ':*');
    }

    public function subscribedTo(): array
    {
        return ['cocktailUpdated'];
    }
}
