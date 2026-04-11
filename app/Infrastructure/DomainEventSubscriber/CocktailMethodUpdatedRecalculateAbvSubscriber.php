<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure\DomainEventSubscriber;

use Kami\Cocktail\Models\Cocktail;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\EventSubscriber;
use BarAssistant\Domain\Cocktail\Event\CocktailMethodUpdated;

final class CocktailMethodUpdatedRecalculateAbvSubscriber implements EventSubscriber
{
    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof CocktailMethodUpdated) {
            return;
        }

        if ($event->previousDilutionPercentage === $event->currentDilutionPercentage) {
            return;
        }

        Cocktail::query()
            ->where('bar_id', $event->barId)
            ->where('cocktail_method_id', $event->methodId)
            ->get()
            ->each(function (Cocktail $cocktail): void {
                $cocktail->abv = $cocktail->getABV();
                $cocktail->save();
            });
    }

    public function subscribedTo(): array
    {
        return ['cocktailMethodUpdated'];
    }
}
