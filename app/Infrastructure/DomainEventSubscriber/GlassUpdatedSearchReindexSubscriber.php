<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure\DomainEventSubscriber;

use Kami\Cocktail\Models\Cocktail;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\EventSubscriber;
use BarAssistant\Domain\Cocktail\Event\GlassUpdated;

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
            ->where('bar_id', $event->barId)
            ->where('glass_id', $event->glassId)
            ->get()
            ->each(static fn (Cocktail $cocktail) => $cocktail->searchable());
    }

    public function subscribedTo(): array
    {
        return ['glassUpdated'];
    }
}
