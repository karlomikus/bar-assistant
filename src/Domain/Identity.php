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
     * Check if the identity has not been persisted yet
     *
     * @phpstan-assert-if-true null $this->getId()
     */
    public function isTransient(): bool;
}
