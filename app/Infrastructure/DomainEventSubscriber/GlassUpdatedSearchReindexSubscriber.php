<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure\DomainEventSubscriber;

use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\EventSubscriber;
use BarAssistant\Domain\Cocktail\Event\GlassUpdated;
use Kami\Cocktail\Models\Cocktail;

final class GlassUpdatedSearchReindexSubscriber implements EventSubscriber
{
    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof GlassUpdated) {
            return;
        }

        if (empty(config('scout.driver'))) {
            return;
        }

        Cocktail::query()
            ->where('bar_id', $event->barId->value)
            ->where('glass_id', $event->glassId->value)
            ->get()
            ->each(static fn (Cocktail $cocktail) => $cocktail->searchable());
    }

    public function subscribedTo(): array
    {
        return ['glassUpdated'];
    }
}
