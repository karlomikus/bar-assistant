<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Exception\DomainException;

final class CocktailCollection implements Identity
{
    private ?CollectionId $id = null;

    /** @var CocktailId[] */
    private array $cocktailIds = [];

    /**
     * @param CocktailId[] $cocktailIds
     */
    private function __construct(
        private readonly BarId $barId,
        private readonly MemberId $memberId,
        private Name $name,
        private RecordTimestamps $recordTimestamps,
        private ?string $description = null,
        private bool $isBarShared = false,
        array $cocktailIds = [],
    ) {
        $this->syncCocktails($cocktailIds);
    }

    /**
     * @param CocktailId[] $cocktailIds
     */
    public static function create(
        BarId $barId,
        MemberId $memberId,
        Name $name,
        RecordTimestamps $recordTimestamps,
        ?string $description = null,
        bool $isBarShared = false,
        array $cocktailIds = [],
    ): self {
        return new self(
            barId: $barId,
            memberId: $memberId,
            name: $name,
            recordTimestamps: $recordTimestamps,
            description: $description,
            isBarShared: $isBarShared,
            cocktailIds: $cocktailIds,
        );
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?CollectionId
    {
        return $this->id;
    }

    public function setId(CollectionId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing collection');
        }

        $this->id = $id;

        return $this;
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    public function getMemberId(): MemberId
    {
        return $this->memberId;
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

    public function isBarShared(): bool
    {
        return $this->isBarShared;
    }

    /**
     * @return CocktailId[]
     */
    public function getCocktailIds(): array
    {
        return $this->cocktailIds;
    }

    public function updateDetails(Name $name, ?string $description, bool $isBarShared): self
    {
        if ($this->isTransient()) {
            throw new DomainException('Cannot update details of a transient collection');
        }

        $this->name = $name;
        $this->description = $description;
        $this->isBarShared = $isBarShared;
        $this->recordTimestamps = $this->recordTimestamps->updatedNow();

        return $this;
    }

    /**
     * @param CocktailId[] $cocktailIds
     */
    public function syncCocktails(array $cocktailIds): self
    {
        $unique = [];

        foreach ($cocktailIds as $cocktailId) {
            $unique[$cocktailId->value] = $cocktailId;
        }

        $this->cocktailIds = array_values($unique);

        if ($this->isTransient() === false) {
            $this->recordTimestamps = $this->recordTimestamps->updatedNow();
        }

        return $this;
    }
}
