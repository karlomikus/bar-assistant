<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\DomainEventDispatcher;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Common\Traits\HasImages;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Cocktail\Event\GlassUpdated;

final class Glass implements Identity
{
    use HasImages;

    private ?GlassId $id = null;

    private function __construct(
        private readonly BarId $barId,
        private Name $name,
        private RecordTimestamps $recordTimestamps,
        private ?string $description = null,
        private ?AmountWithUnits $volume = null,
    ) {
    }

    public static function create(
        BarId $barId,
        Name $name,
        RecordTimestamps $recordTimestamps,
        ?string $description = null,
        ?AmountWithUnits $volume = null,
    ): self {
        return new self(
            barId: $barId,
            name: $name,
            recordTimestamps: $recordTimestamps,
            description: $description,
            volume: $volume,
        );
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?GlassId
    {
        return $this->id;
    }

    public function setId(GlassId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing glass');
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

    public function getVolume(): ?AmountWithUnits
    {
        return $this->volume;
    }

    public function updateDetails(
        Name $name,
        ?string $description = null,
        ?AmountWithUnits $volume = null,
    ): self {
        if ($this->isTransient()) {
            throw new DomainException('Cannot update details of a transient glass');
        }

        $this->name = $name;
        $this->description = $description;
        $this->volume = $volume;
        $this->recordTimestamps = $this->recordTimestamps->updatedNow();

        DomainEventDispatcher::instance()->publish(new GlassUpdated(
            barId: $this->barId->value,
            glassId: $this->getId()->value ?? 0,
            volume: $volume?->amountMin,
            volumeUnits: $volume?->units->value,
            volumeMax: $volume?->amountMax,
        ));

        return $this;
    }
}
