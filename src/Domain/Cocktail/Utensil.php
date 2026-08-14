<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Exception\DomainException;

final class Utensil implements Identity
{
    private ?UtensilId $id = null;

    private function __construct(
        private readonly BarId $barId,
        private Name $name,
        private RecordTimestamps $recordTimestamps,
        private ?string $description = null,
    ) {
    }

    public static function create(
        BarId $barId,
        Name $name,
        RecordTimestamps $recordTimestamps,
        ?string $description = null,
    ): self {
        return new self(
            barId: $barId,
            name: $name,
            recordTimestamps: $recordTimestamps,
            description: $description,
        );
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?UtensilId
    {
        return $this->id;
    }

    public function setId(UtensilId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing utensil');
        }

        $this->id = $id;

        return $this;
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function updateDetails(Name $name, ?string $description = null): self
    {
        if ($this->isTransient()) {
            throw new DomainException('Cannot update details of a transient utensil');
        }

        $this->name = $name;
        $this->description = $description;
        $this->recordTimestamps = $this->recordTimestamps->updatedNow();

        return $this;
    }
}
