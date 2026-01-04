<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Identity;

final class Bar implements Identity
{
    private ?BarId $id = null;

    public function __construct(
        private string $name,
        private BarInventory $inventory,
    ) {
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?BarId
    {
        return $this->id;
    }

    public function setId(BarId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing bar');
        }

        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInventory(): BarInventory
    {
        return $this->inventory;
    }
}
