<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Dilution;
use BarAssistant\Domain\DomainEventDispatcher;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Cocktail\Event\CocktailMethodUpdated;

/**
 * CocktailMethod Aggregate Root
 *
 * Represents a preparation technique used to make a cocktail (e.g. stirring, shaking, blending).
 * Each method has a standard dilution percentage that affects the final drink's ABV calculation.
 */
final class CocktailMethod implements Identity
{
    private ?MethodId $id = null;

    /**
     * @param BarId $barId Bar identifier
     * @param Name $name Name of the cocktail method
     * @param Dilution $dilution Dilution percentage applied by this method
     * @param RecordTimestamps $recordTimestamps Timestamps for creation and modification
     * @param null|string $description Optional description of the method
     */
    private function __construct(
        private readonly BarId $barId,
        private Name $name,
        private Dilution $dilution,
        private RecordTimestamps $recordTimestamps,
        private ?string $description = null,
    ) {
    }

    public static function create(
        BarId $barId,
        Name $name,
        Dilution $dilution,
        RecordTimestamps $recordTimestamps,
        ?string $description = null,
    ): self {
        return new self(
            barId: $barId,
            name: $name,
            dilution: $dilution,
            recordTimestamps: $recordTimestamps,
            description: $description,
        );
    }

    public function getId(): ?MethodId
    {
        return $this->id;
    }

    public function setId(MethodId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing cocktail method');
        }

        $this->id = $id;

        return $this;
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getDilution(): Dilution
    {
        return $this->dilution;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Update the cocktail method details
     *
     * @param Name $name New name
     * @param Dilution $dilution New dilution percentage
     * @param null|string $description New description
     */
    public function updateDetails(
        Name $name,
        Dilution $dilution,
        ?string $description = null,
    ): self {
        if ($this->isTransient()) {
            throw new DomainException('Cannot update details of a transient cocktail method');
        }

        $previousDilutionPercentage = $this->dilution->toFloat();
        $this->name = $name;
        $this->dilution = $dilution;
        $this->description = $description;
        $this->recordTimestamps = $this->recordTimestamps->updatedNow();

        DomainEventDispatcher::instance()->publish(new CocktailMethodUpdated(
            barId: $this->barId->value,
            methodId: $this->getId()->value ?? 0,
            previousDilutionPercentage: $previousDilutionPercentage,
            currentDilutionPercentage: $this->dilution->toFloat(),
        ));

        return $this;
    }
}
