<?php

declare(strict_types=1);

namespace BarAssistant\Domain;

interface AggregateRoot
{
    /**
     * Get the unique identifier of the aggregate root
     */
    public function getId(): ?AggregateRootId;

    /**
     * Check if the aggregate root has not been persisted yet
     */
    public function isTransient(): bool;
}
