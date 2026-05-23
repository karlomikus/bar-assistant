<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Event;

interface EventSubscriber
{
    /**
     * Handle the domain event
     */
    public function handle(DomainEvent $event): void;

    /**
     * Get the list of event names this subscriber listens to
     *
     * @return array<string>
     */
    public function subscribedTo(): array;
}
