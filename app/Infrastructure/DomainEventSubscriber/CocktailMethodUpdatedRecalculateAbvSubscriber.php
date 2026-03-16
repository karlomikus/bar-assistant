<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure\DomainEventSubscriber;

use BarAssistant\Domain\Cocktail\Event\CocktailMethodUpdated;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\EventSubscriber;
use Kami\Cocktail\Models\Cocktail;

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
            ->where('bar_id', $event->barId->value)
            ->where('cocktail_method_id', $event->methodId->value)
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
