<?php

declare(strict_types=1);

namespace BarAssistant\Domain;

interface Identity
{
    /**
     * Get the unique identifier of the identity
     */
    public function getId(): ?Identifier;

    /**
     * Check if the identity has not been persisted yet.
     *
     * Needed since we want to continue working with legacy
     * database schema which uses auto increments.
     *
     * Annoying to work with, so in future it would
     * be nice to move to some kind of UID
     *
     * @phpstan-assert-if-true null $this->getId()
     */
    public function isTransient(): bool;
}
