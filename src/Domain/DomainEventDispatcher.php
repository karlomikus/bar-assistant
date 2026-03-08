<?php

declare(strict_types=1);

namespace BarAssistant\Domain;

use ReflectionClass;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\DomainEventName;
use BarAssistant\Domain\Event\EventSubscriber;
use BarAssistant\Domain\Exception\DomainException;

final class DomainEventDispatcher
{
    /** @var array<string, EventSubscriber[]> */
    private array $subscribers = [];

    private static ?self $instance = null;

    /**
     * Get the singleton instance of the event publisher
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Subscribe to domain events
     */
    public function subscribe(EventSubscriber $subscriber): void
    {
        foreach ($subscriber->subscribedTo() as $eventName) {
            if (!isset($this->subscribers[$eventName])) {
                $this->subscribers[$eventName] = [];
            }

            $this->subscribers[$eventName][] = $subscriber;
        }
    }

    /**
     * Publish a domain event to all subscribers
     */
    public function publish(DomainEvent $event): void
    {
        $class = new ReflectionClass($event);
        $attributes = $class->getAttributes(DomainEventName::class);
        if (empty($attributes)) {
            throw new DomainException('Domain event class must have a DomainEventName attribute.');
        }

        /** @var DomainEventName $domainEventName */
        $domainEventName = $attributes[0]->newInstance();
        $eventName = $domainEventName->name;

        if (!isset($this->subscribers[$eventName])) {
            return;
        }

        foreach ($this->subscribers[$eventName] as $subscriber) {
            $subscriber->handle($event);
        }
    }

    /**
     * Clear all subscribers
     */
    public function clearSubscribers(): void
    {
        $this->subscribers = [];
    }

    /**
     * Reset the singleton instance
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
